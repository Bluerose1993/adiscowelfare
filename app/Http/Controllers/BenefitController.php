<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBenefitRequest;
use App\Models\Benefit;
use App\Models\BenefitRequest;
use App\Models\BenefitDeletionRequest;
use App\Models\BenefitType;
use App\Models\Setting;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class BenefitController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Benefit::class);

        $status = $request->query('status') ?: 'all';
        abort_unless(in_array($status, ['all', 'pending', 'submitted', 'under_review', 'returned', 'approved', 'paid', 'rejected', 'cancelled'], true), 422);

        $requests = BenefitRequest::query()->with(['staff', 'benefitType', 'resultingBenefit'])->latest();
        if ($status === 'pending') {
            $requests->whereIn('status', [BenefitRequest::STATUS_SUBMITTED, BenefitRequest::STATUS_UNDER_REVIEW]);
        } elseif ($status !== 'all') {
            $requests->where('status', $status);
        }

        // Approved requests already have a linked benefit. Show those once in the request register.
        $query = Benefit::query()->with(['staff', 'benefitType'])
            ->whereNotIn('id', BenefitRequest::query()->select('resulting_benefit_id')->whereNotNull('resulting_benefit_id'))
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('admin.benefits.index', [
            'benefits' => $query->paginate(50, ['*'], 'benefits_page')->withQueryString(),
            'requests' => $requests->paginate(50, ['*'], 'requests_page')->withQueryString(),
            'pendingDeletionRequests' => BenefitDeletionRequest::query()->where('status', 'pending')->with(['benefit.staff', 'requester'])->latest()->get(),
            'status' => $status,
        ]);
    }

    public function requestDeletion(Request $request, Benefit $benefit, AuditService $audit): RedirectResponse
    {
        $this->authorize('delete', $benefit);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500'], 'password' => ['required', 'current_password']]);
        if (Setting::value('system_mode', 'production') === 'debug') {
            $old = $benefit->toArray();
            $benefit->delete();
            $audit->log('benefit_deleted_debug_mode', $benefit, $old, ['reason' => $validated['reason']], $request);
            return back()->with('success', 'Benefit deleted immediately in Debug mode.');
        }
        if ($benefit->deletionRequests()->where('status', 'pending')->exists()) {
            return back()->withErrors(['reason' => 'A deletion request is already awaiting approval for this benefit.']);
        }
        $deletion = $benefit->deletionRequests()->create(['requested_by' => $request->user()->id, 'reason' => $validated['reason'], 'status' => 'pending']);
        $audit->log('benefit_deletion_requested', $benefit, [], ['request_id' => $deletion->id, 'reason' => $validated['reason']], $request);
        return back()->with('success', 'Benefit deletion sent for second-admin approval.');
    }

    public function approveDeletion(Request $request, BenefitDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422, 'This request has already been reviewed.');
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'You cannot approve your own request.');
        $request->validate(['password' => ['required', 'current_password']]);
        $benefit = $deletionRequest->benefit;
        abort_if(! $benefit || $benefit->trashed(), 422, 'This benefit has already been deleted.');
        DB::transaction(function () use ($request, $benefit, $deletionRequest, $audit) {
            $old = $benefit->toArray();
            $deletionRequest->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            $benefit->delete();
            $audit->log('benefit_deletion_approved', $benefit, $old, ['request_id' => $deletionRequest->id], $request);
        });
        return redirect()->route('admin.benefits.index')->with('success', 'Benefit deletion approved and completed.');
    }

    public function rejectDeletion(Request $request, BenefitDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422);
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'You cannot review your own request.');
        $validated = $request->validate(['password' => ['required', 'current_password'], 'review_notes' => ['nullable', 'string', 'max:500']]);
        $deletionRequest->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $validated['review_notes'] ?? null]);
        $audit->log('benefit_deletion_rejected', $deletionRequest->benefit, [], ['request_id' => $deletionRequest->id], $request);
        return back()->with('success', 'Benefit deletion request rejected.');
    }

    public function create(): View
    {
        $this->authorize('create', Benefit::class);

        return view('admin.benefits.form', [
            'benefit' => new Benefit(['status' => Benefit::STATUS_PENDING]),
            'staff' => Staff::query()->active()->orderBy('full_name')->get(),
            'benefitTypes' => BenefitType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBenefitRequest $request, AuditService $audit): RedirectResponse
    {
        $benefit = Benefit::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'approved_by' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? $request->user()->id : null,
            'paid_by' => $request->input('status') === Benefit::STATUS_PAID ? $request->user()->id : null,
            'approved_date' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? now()->toDateString() : null,
            'payment_date' => $request->input('status') === Benefit::STATUS_PAID ? ($request->input('payment_date') ?: now()->toDateString()) : $request->input('payment_date'),
        ]);
        $audit->log('benefit_created', $benefit, [], $benefit->toArray());

        return redirect()->route('admin.benefits.index')->with('success', 'Benefit recorded.');
    }

    public function edit(Benefit $benefit): View
    {
        $this->authorize('update', $benefit);

        return view('admin.benefits.form', [
            'benefit' => $benefit,
            'staff' => Staff::query()->active()->orderBy('full_name')->get(),
            'benefitTypes' => BenefitType::query()->orderBy('name')->get(),
        ]);
    }

    public function update(StoreBenefitRequest $request, Benefit $benefit, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $benefit);
        $old = $benefit->toArray();
        $benefit->update([
            ...$request->validated(),
            'approved_by' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? ($benefit->approved_by ?: $request->user()->id) : $benefit->approved_by,
            'paid_by' => $request->input('status') === Benefit::STATUS_PAID ? ($benefit->paid_by ?: $request->user()->id) : $benefit->paid_by,
            'approved_date' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? ($benefit->approved_date ?: now()->toDateString()) : $benefit->approved_date,
            'payment_date' => $request->input('status') === Benefit::STATUS_PAID ? ($request->input('payment_date') ?: now()->toDateString()) : $request->input('payment_date'),
        ]);
        if ($benefit->status === Benefit::STATUS_PAID) {
            BenefitRequest::query()->where('resulting_benefit_id', $benefit->id)
                ->whereIn('status', [BenefitRequest::STATUS_APPROVED, BenefitRequest::STATUS_PAID])
                ->update(['status' => BenefitRequest::STATUS_PAID]);
        }
        $audit->log('benefit_updated', $benefit, $old, $benefit->fresh()->toArray());

        return redirect()->route('admin.benefits.index')->with('success', 'Benefit updated.');
    }

    public function markPaid(Request $request, Benefit $benefit, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $benefit);
        $request->validate(['payment_date' => ['required', 'date']]);

        $old = $benefit->toArray();
        $benefit->update([
            'status' => Benefit::STATUS_PAID,
            'payment_date' => $request->input('payment_date'),
            'paid_by' => $request->user()->id,
            'approved_by' => $benefit->approved_by ?: $request->user()->id,
            'approved_date' => $benefit->approved_date ?: now()->toDateString(),
        ]);
        BenefitRequest::query()->where('resulting_benefit_id', $benefit->id)
            ->whereIn('status', [BenefitRequest::STATUS_APPROVED, BenefitRequest::STATUS_PAID])
            ->update(['status' => BenefitRequest::STATUS_PAID]);
        $audit->log('benefit_marked_paid', $benefit, $old, $benefit->fresh()->toArray());

        return back()->with('success', 'Benefit marked as paid.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewBenefitRequestRequest;
use App\Http\Requests\SubmitBenefitRequestRequest;
use App\Models\BenefitRequest;
use App\Models\BenefitRequestAttachment;
use App\Models\BenefitRequestDeletionRequest;
use App\Models\BenefitType;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\BenefitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BenefitRequestController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $query = BenefitRequest::query()->with(['staff', 'benefitType', 'resultingBenefit'])->latest();
        $status = $request->input('status', 'pending');

        if ($status === 'pending') {
            $query->whereIn('status', [BenefitRequest::STATUS_SUBMITTED, BenefitRequest::STATUS_UNDER_REVIEW]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        return view('admin.benefit-requests.index', [
            'requests' => $query->paginate(50)->withQueryString(),
            'status' => $status,
            'pendingDeletionRequests' => BenefitRequestDeletionRequest::query()->where('status', 'pending')->with(['benefitRequest.staff', 'requester'])->latest()->get(),
        ]);
    }

    public function requestDeletion(Request $request, BenefitRequest $benefitRequest, AuditService $audit): RedirectResponse
    {
        $this->authorize('review', $benefitRequest);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:500'], 'password' => ['required', 'current_password']]);
        if (Setting::value('system_mode', 'production') === 'debug') {
            $this->deleteRequest($benefitRequest);
            $audit->log('benefit_request_deleted_debug_mode', null, [], ['request_id' => $benefitRequest->id, 'reason' => $validated['reason']], $request);
            return redirect()->route('admin.benefit-requests.index')->with('success', 'Benefit request deleted immediately in Debug mode.');
        }
        if ($benefitRequest->deletionRequests()->where('status', 'pending')->exists()) {
            return back()->withErrors(['reason' => 'A deletion request is already awaiting approval.']);
        }
        $deletion = $benefitRequest->deletionRequests()->create(['requested_by' => $request->user()->id, 'reason' => $validated['reason'], 'status' => 'pending']);
        $audit->log('benefit_request_deletion_requested', $benefitRequest, [], ['deletion_request_id' => $deletion->id], $request);
        return back()->with('success', 'Benefit request deletion sent for second-admin approval.');
    }

    public function approveDeletion(Request $request, BenefitRequestDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422, 'This request has already been reviewed.');
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'You cannot approve your own request.');
        $request->validate(['password' => ['required', 'current_password']]);
        $benefitRequest = BenefitRequest::query()->findOrFail($deletionRequest->benefit_request_id);
        $recordId = $benefitRequest->id;
        $this->deleteRequest($benefitRequest);
        $audit->log('benefit_request_deletion_approved', null, [], ['deleted_request_id' => $recordId], $request);
        return redirect()->route('admin.benefit-requests.index')->with('success', 'Benefit request deletion approved and completed.');
    }

    public function rejectDeletion(Request $request, BenefitRequestDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422);
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'You cannot review your own request.');
        $validated = $request->validate(['password' => ['required', 'current_password']]);
        $deletionRequest->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $audit->log('benefit_request_deletion_rejected', $deletionRequest->benefitRequest, [], ['deletion_request_id' => $deletionRequest->id], $request);
        return back()->with('success', 'Benefit request deletion rejected.');
    }

    private function deleteRequest(BenefitRequest $benefitRequest): void
    {
        $paths = $benefitRequest->attachments()->pluck('path');
        DB::transaction(fn () => $benefitRequest->delete());
        $paths->each(fn (string $path) => Storage::disk('public')->delete($path));
    }

    public function adminShow(BenefitRequest $benefitRequest): View
    {
        $this->authorize('review', $benefitRequest);

        return view('admin.benefit-requests.show', [
            'requestRecord' => $benefitRequest->load(['staff', 'benefitType', 'attachments', 'resultingBenefit']),
        ]);
    }

    public function adminPrint(BenefitRequest $benefitRequest): View
    {
        return view('benefit-requests.print', [
            'requestRecord' => $benefitRequest->load(['staff', 'benefitType', 'reviewer']),
        ]);
    }

    public function review(ReviewBenefitRequestRequest $request, BenefitRequest $benefitRequest, BenefitService $benefits, AuditService $audit): RedirectResponse
    {
        $this->authorize('review', $benefitRequest);
        $old = $benefitRequest->toArray();

        if ($benefitRequest->resulting_benefit_id && ! in_array($request->input('status'), [BenefitRequest::STATUS_APPROVED, BenefitRequest::STATUS_PAID], true)) {
            throw ValidationException::withMessages(['status' => 'An approved benefit cannot be returned to a review status.']);
        }

        if ($request->input('status') === BenefitRequest::STATUS_APPROVED) {
            $benefit = $benefits->approveRequest(
                $benefitRequest,
                (float) $request->input('approved_amount'),
                $request->input('review_notes'),
                $request->user()->id
            );
            $audit->log('benefit_request_approved', $benefitRequest, $old, $benefitRequest->fresh()->toArray());
            $audit->log('benefit_created_from_request', $benefit, [], $benefit->toArray());

            return redirect()->route('admin.benefits.index')->with('success', 'Benefit request approved and moved to All Benefits.');
        }

        if ($request->input('status') === BenefitRequest::STATUS_PAID) {
            if (! $benefitRequest->resultingBenefit) {
                throw ValidationException::withMessages(['status' => 'Approve this request before marking it paid.']);
            }
            DB::transaction(function () use ($request, $benefitRequest) {
                $benefitRequest->resultingBenefit->update([
                    'status' => 'paid',
                    'payment_date' => $benefitRequest->resultingBenefit->payment_date ?: now()->toDateString(),
                    'paid_by' => $request->user()->id,
                ]);
                $benefitRequest->update(['status' => BenefitRequest::STATUS_PAID]);
            });
            $audit->log('benefit_request_paid', $benefitRequest, $old, $benefitRequest->fresh()->toArray(), $request);

            return redirect()->route('admin.benefits.index', ['status' => 'paid'])->with('success', 'Benefit marked as paid.');
        }

        $benefitRequest->update([
            'status' => $request->input('status'),
            'approved_amount' => $request->filled('approved_amount') ? $request->input('approved_amount') : $benefitRequest->approved_amount,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);
        $audit->log('benefit_request_'.$request->input('status'), $benefitRequest, $old, $benefitRequest->fresh()->toArray());

        return back()->with('success', 'Benefit request updated.');
    }

    public function staffIndex(Request $request): View
    {
        $staff = $request->user()->staff;

        return view('staff.requests.index', [
            'requests' => BenefitRequest::query()->with('benefitType')->where('staff_id', $staff->id)->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BenefitRequest::class);

        return view('staff.requests.form', [
            'benefitTypes' => BenefitType::query()->where('is_active', true)->orderBy('name')->get(),
            'requestRecord' => null,
        ]);
    }

    public function staffEdit(Request $request, BenefitRequest $benefitRequest): View
    {
        $this->authorize('view', $benefitRequest);
        abort_unless($benefitRequest->staff_id === $request->user()->staff?->id && $benefitRequest->status === BenefitRequest::STATUS_RETURNED, 403, 'Only returned requests can be adjusted.');

        return view('staff.requests.form', [
            'benefitTypes' => BenefitType::query()->where('is_active', true)->orderBy('name')->get(),
            'requestRecord' => $benefitRequest->load('attachments'),
        ]);
    }

    public function staffUpdate(Request $request, BenefitRequest $benefitRequest, AuditService $audit): RedirectResponse
    {
        $this->authorize('view', $benefitRequest);
        abort_unless($benefitRequest->staff_id === $request->user()->staff?->id && $benefitRequest->status === BenefitRequest::STATUS_RETURNED, 403, 'Only returned requests can be adjusted.');
        $validated = $request->validate([
            'benefit_type_id' => ['required', Rule::exists('benefit_types', 'id')->where('is_active', true)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'incident_date' => ['nullable', 'date'],
            'attachment' => [$benefitRequest->attachments()->exists() ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ], [], ['attachment' => 'upload proof']);
        $benefitType = BenefitType::query()->where('is_active', true)->findOrFail($validated['benefit_type_id']);
        $old = $benefitRequest->toArray();
        $oldPaths = collect();

        DB::transaction(function () use ($request, $benefitRequest, $benefitType, $validated, &$oldPaths) {
            $benefitRequest->update([
                'benefit_type_id' => $benefitType->id,
                'subject' => $validated['subject'],
                'description' => $validated['description'],
                'incident_date' => $validated['incident_date'] ?? null,
                'requested_amount' => $benefitType->default_amount,
                'approved_amount' => null,
                'status' => BenefitRequest::STATUS_SUBMITTED,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_notes' => null,
            ]);
            if ($request->hasFile('attachment')) {
                $oldPaths = $benefitRequest->attachments()->pluck('path');
                $benefitRequest->attachments()->delete();
                $file = $request->file('attachment');
                $stored = $file->store('benefit-request-attachments', 'public');
                BenefitRequestAttachment::query()->create([
                    'benefit_request_id' => $benefitRequest->id,
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_filename' => Str::afterLast($stored, '/'),
                    'path' => $stored,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        });
        $oldPaths->each(fn (string $path) => Storage::disk('public')->delete($path));
        $audit->log('benefit_request_adjusted_and_resubmitted', $benefitRequest, $old, $benefitRequest->fresh()->toArray(), $request);

        return redirect()->route('staff.requests.show', $benefitRequest)->with('success', 'Your adjustments were submitted for review.');
    }

    public function store(SubmitBenefitRequestRequest $request, AuditService $audit): RedirectResponse
    {
        $staff = $request->user()->staff;
        $benefitType = BenefitType::query()->where('is_active', true)->findOrFail($request->integer('benefit_type_id'));

        $benefitRequest = DB::transaction(function () use ($request, $staff, $benefitType, $audit) {
            $benefitRequest = BenefitRequest::query()->create([
                ...$request->safe()->except(['attachment', 'requested_amount']),
                'requested_amount' => $benefitType->default_amount,
                'staff_id' => $staff->id,
                'status' => BenefitRequest::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $stored = $file->store('benefit-request-attachments', 'public');
                BenefitRequestAttachment::query()->create([
                    'benefit_request_id' => $benefitRequest->id,
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_filename' => Str::afterLast($stored, '/'),
                    'path' => $stored,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }

            $audit->log('benefit_request_submitted', $benefitRequest, [], $benefitRequest->toArray());

            return $benefitRequest;
        });

        return redirect()->route('staff.requests.show', $benefitRequest)->with('success', 'Benefit request submitted.');
    }

    public function staffShow(Request $request, BenefitRequest $benefitRequest): View
    {
        $this->authorize('view', $benefitRequest);

        return view('staff.requests.show', [
            'requestRecord' => $benefitRequest->load(['benefitType', 'attachments', 'resultingBenefit']),
        ]);
    }

    public function confirmReceipt(Request $request, BenefitRequest $benefitRequest, AuditService $audit): RedirectResponse
    {
        $this->authorize('view', $benefitRequest);
        $validated = $request->validate([
            'received_amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'confirm_receipt' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $benefitRequest, $validated, $audit) {
            $record = BenefitRequest::query()->lockForUpdate()->findOrFail($benefitRequest->id);
            if (! in_array($record->status, [BenefitRequest::STATUS_APPROVED, BenefitRequest::STATUS_PAID], true)
                || $record->staff_id !== $request->user()->staff?->id || $record->receipt_confirmed_at) {
                throw ValidationException::withMessages(['received_amount' => 'This request cannot be confirmed again or has not yet been approved.']);
            }

            $record->update([
                'received_amount' => $validated['received_amount'],
                'receipt_confirmed_at' => now(),
            ]);
            $audit->log('benefit_receipt_confirmed', $record, [], [
                'received_amount' => $record->received_amount,
                'approved_amount' => $record->approved_amount,
                'receipt_confirmed_at' => $record->receipt_confirmed_at,
            ], $request);
        });

        return back()->with('success', 'Your confirmation of the amount received has been recorded.');
    }

    public function staffPrint(BenefitRequest $benefitRequest): View
    {
        $this->authorize('view', $benefitRequest);

        return view('benefit-requests.print', [
            'requestRecord' => $benefitRequest->load(['staff', 'benefitType', 'reviewer']),
        ]);
    }
}

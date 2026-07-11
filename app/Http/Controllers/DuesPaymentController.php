<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDuesPaymentRequest;
use App\Models\Benefit;
use App\Models\DuesPayment;
use App\Models\DuesPaymentDeletionRequest;
use App\Models\Staff;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\DuesCalculationService;
use App\Services\DuesPaymentAllocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DuesPaymentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', DuesPayment::class);

        return view('admin.dues.index', [
            'payments' => DuesPayment::query()->with(['staff', 'recorder', 'deletionRequests' => fn ($query) => $query->where('status', 'pending')])->latest('payment_date')->paginate(50),
            'pendingDeletionRequests' => DuesPaymentDeletionRequest::query()->where('status', 'pending')->with(['payment.staff', 'requester'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', DuesPayment::class);

        return view('admin.dues.record', [
            'months' => DuesCalculationService::MONTHS,
            'year' => now()->year,
        ]);
    }

    public function search(Request $request, DuesCalculationService $dues): JsonResponse
    {
        $this->authorize('create', DuesPayment::class);
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $year = (int) $request->integer('year', now()->year);

        $results = Staff::query()
            ->active()
            ->search($request->input('q'))
            ->orderBy('full_name')
            ->limit(12)
            ->get()
            ->map(fn (Staff $staff) => [
                'id' => $staff->id,
                'staff_id' => $staff->staff_id,
                'full_name' => $staff->full_name,
                'phone' => $staff->phone,
                'department' => $staff->department,
                'year_total' => $dues->totalPaid($staff, $year),
            ]);

        return response()->json($results);
    }

    public function summary(Staff $staff, DuesCalculationService $dues): JsonResponse
    {
        $this->authorize('view', $staff);
        $year = (int) request('year', now()->year);

        return response()->json([
            'staff' => $staff,
            'year_total' => $dues->totalPaid($staff, $year),
            'monthly' => $dues->monthlyBreakdown($staff, $year),
            'benefits_received' => (float) $staff->benefits()->where('status', Benefit::STATUS_PAID)->sum('amount'),
            'pending_benefits' => (float) $staff->benefits()->whereIn('status', [Benefit::STATUS_PENDING, Benefit::STATUS_APPROVED])->sum('amount'),
        ]);
    }

    public function store(StoreDuesPaymentRequest $request, AuditService $audit, DuesPaymentAllocationService $allocator): RedirectResponse
    {
        $payments = DB::transaction(function () use ($request, $audit, $allocator) {
            $payments = $allocator->record($request->validated(), $request->user()->id);
            foreach ($payments as $payment) {
                $audit->log('dues_payment_created', $payment, [], $payment->toArray());
            }

            return $payments;
        });

        $first = $payments[0];
        $message = count($payments) > 1
            ? 'Payment recorded and automatically allocated across '.count($payments).' months.'
            : 'Dues payment recorded.';

        return back()->with('success', $message)->with('selected_staff_id', $first->staff_id);
    }

    public function requestDeletion(Request $request, DuesPayment $duesPayment, AuditService $audit): RedirectResponse
    {
        $this->authorize('delete', $duesPayment);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'password' => ['required', 'current_password'],
        ]);
        if (Setting::value('system_mode', 'production') === 'debug') {
            $old = $duesPayment->toArray();
            $duesPayment->forceFill([
                'deleted_by' => $request->user()->id,
                'deleted_reason' => '[Debug mode] '.$validated['reason'],
            ])->save();
            $duesPayment->delete();
            $audit->log('dues_payment_deleted_debug_mode', $duesPayment, $old, ['reason' => $validated['reason']], $request);

            return back()->with('success', 'Payment deleted immediately because Debug mode is active.');
        }
        if ($duesPayment->deletionRequests()->where('status', 'pending')->exists()) {
            return back()->withErrors(['reason' => 'A deletion request is already awaiting approval for this payment.']);
        }
        $deletionRequest = $duesPayment->deletionRequests()->create([
            'requested_by' => $request->user()->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);
        $audit->log('dues_payment_deletion_requested', $duesPayment, [], ['request_id' => $deletionRequest->id, 'reason' => $validated['reason']], $request);

        return back()->with('success', 'Deletion request submitted. A different administrator must approve it.');
    }

    public function approveDeletion(Request $request, DuesPaymentDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422, 'This request has already been reviewed.');
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'The requesting administrator cannot approve their own deletion request.');
        $request->validate(['password' => ['required', 'current_password']]);
        $payment = $deletionRequest->payment;
        abort_if(! $payment || $payment->trashed(), 422, 'This payment has already been deleted.');

        DB::transaction(function () use ($request, $deletionRequest, $payment, $audit) {
            $old = $payment->toArray();
            $payment->forceFill([
            'deleted_by' => $request->user()->id,
            'deleted_reason' => $deletionRequest->reason,
            ])->save();
            $payment->delete();
            $deletionRequest->update(['status' => 'approved', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            $audit->log('dues_payment_deletion_approved', $payment, $old, ['request_id' => $deletionRequest->id, 'reason' => $deletionRequest->reason], $request);
        });

        return back()->with('success', 'Deletion approved. The payment has been removed and audited.');
    }

    public function rejectDeletion(Request $request, DuesPaymentDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422, 'This request has already been reviewed.');
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'The requesting administrator cannot review their own request.');
        $validated = $request->validate(['password' => ['required', 'current_password'], 'review_notes' => ['nullable', 'string', 'max:500']]);
        $deletionRequest->update(['status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $validated['review_notes'] ?? null]);
        $audit->log('dues_payment_deletion_rejected', $deletionRequest->payment, [], ['request_id' => $deletionRequest->id], $request);

        return back()->with('success', 'Deletion request rejected. The payment remains active.');
    }
}

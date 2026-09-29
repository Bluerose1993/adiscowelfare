<?php

namespace App\Http\Controllers;

use App\Exports\StaffLockersExport;
use App\Models\LockerRequest;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LockerController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $filters = $this->filters($request);
        return view('admin.lockers.index', [
            'staff' => Staff::query()->search($filters['search'])->lockerAssignment($filters['assignment'])
                ->orderBy('full_name')->paginate(50)->withQueryString(),
            'pendingRequests' => LockerRequest::query()->where('status', LockerRequest::STATUS_PENDING)->with('staff')->latest()->get(),
            ...$filters,
        ]);
    }

    public function printRegister(Request $request): View
    {
        $filters = $this->filters($request);

        return view('admin.lockers.print', [
            'staff' => Staff::query()
                ->search($filters['search'])->lockerAssignment($filters['assignment'])
                ->orderBy('full_name')
                ->get(),
            ...$filters,
        ]);
    }

    public function exportRegister(Request $request): BinaryFileResponse
    {
        $filters = $this->filters($request);

        return Excel::download(
            new StaffLockersExport($filters['search'], $filters['assignment']),
            'staff-lockers-'.$filters['assignment'].'-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'assignment' => ['nullable', Rule::in(['all', 'assigned', 'unassigned'])],
        ]);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'assignment' => $validated['assignment'] ?? 'all',
        ];
    }

    public function assign(Request $request, Staff $staff, AuditService $audit): RedirectResponse
    {
        $validated = $request->validate(['locker_number' => ['nullable', 'string', 'max:100', Rule::unique('staff', 'locker_number')->ignore($staff->id)]]);
        $old = $staff->locker_number;
        $staff->update(['locker_number' => $validated['locker_number'] ?: null]);
        $audit->log('locker_assigned_by_admin', $staff, ['locker_number' => $old], ['locker_number' => $staff->locker_number], $request);
        return back()->with('success', $staff->locker_number ? "Locker {$staff->locker_number} assigned to {$staff->full_name}." : "Locker assignment removed from {$staff->full_name}.");
    }

    public function approve(Request $request, LockerRequest $lockerRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($lockerRequest->status === LockerRequest::STATUS_PENDING, 422, 'This locker request has already been reviewed.');
        $validated = $request->validate([
            'locker_number' => ['required', 'string', 'max:100', Rule::unique('staff', 'locker_number')->ignore($lockerRequest->staff_id)],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);
        DB::transaction(function () use ($request, $lockerRequest, $validated, $audit) {
            $lockerRequest->staff->update(['locker_number' => $validated['locker_number']]);
            $lockerRequest->update(['status' => LockerRequest::STATUS_APPROVED, 'assigned_locker_number' => $validated['locker_number'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $validated['review_notes'] ?? null]);
            $audit->log('locker_request_approved', $lockerRequest->staff, [], ['locker_request_id' => $lockerRequest->id, 'locker_number' => $validated['locker_number']], $request);
        });
        return back()->with('success', 'Locker request approved and the locker now appears on the staff profile.');
    }

    public function reject(Request $request, LockerRequest $lockerRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($lockerRequest->status === LockerRequest::STATUS_PENDING, 422, 'This locker request has already been reviewed.');
        $validated = $request->validate(['review_notes' => ['required', 'string', 'max:500']]);
        $lockerRequest->update(['status' => LockerRequest::STATUS_REJECTED, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(), 'review_notes' => $validated['review_notes']]);
        $audit->log('locker_request_rejected', $lockerRequest->staff, [], ['locker_request_id' => $lockerRequest->id], $request);
        return back()->with('success', 'Locker request rejected.');
    }

    public function staffCreate(Request $request): View
    {
        return view('staff.lockers', ['staff' => $request->user()->staff, 'requests' => $request->user()->staff->lockerRequests()->latest()->get()]);
    }

    public function staffStore(Request $request, AuditService $audit): RedirectResponse
    {
        $staff = $request->user()->staff;
        if ($staff->lockerRequests()->where('status', LockerRequest::STATUS_PENDING)->exists()) return back()->withErrors(['locker' => 'You already have a locker application awaiting review.']);
        $validated = $request->validate(['preferred_locker_number' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:500']]);
        $lockerRequest = $staff->lockerRequests()->create($validated + ['status' => LockerRequest::STATUS_PENDING]);
        $audit->log('locker_requested', $staff, [], ['locker_request_id' => $lockerRequest->id], $request);
        return back()->with('success', 'Your locker application has been submitted for approval.');
    }
}

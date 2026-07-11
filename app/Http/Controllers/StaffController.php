<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStaffRequest;
use App\Models\AuditLog;
use App\Models\Benefit;
use App\Models\BenefitRequest;
use App\Models\DuesPayment;
use App\Models\Staff;
use App\Models\StaffDeletionRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DuesCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Staff::class);

        $staff = Staff::query()
                ->search($request->input('search'))
                ->orderBy('full_name')
                ->paginate(50)
                ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('admin.staff.partials.results', compact('staff'))->render(),
                'count' => $staff->total(),
            ]);
        }

        return view('admin.staff.index', [
            'staff' => $staff,
            'pendingDeletionRequests' => StaffDeletionRequest::query()->where('status', 'pending')->with(['staff', 'requester'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Staff::class);

        return view('admin.staff.form', ['staff' => new Staff()]);
    }

    public function store(StoreStaffRequest $request, AuditService $audit): RedirectResponse
    {
        $this->authorize('create', Staff::class);

        $staff = DB::transaction(function () use ($request, $audit) {
            $data = $request->safe()->except(['create_user', 'temporary_password']);
            $data['is_active'] = $request->boolean('is_active', true);
            $staff = Staff::query()->create($data);

            if ($request->boolean('create_user')) {
                $this->createLoginAccount($staff, $request->input('temporary_password') ?: 'ChangeMe123!');
            }

            $audit->log('staff_created', $staff, [], $staff->toArray());

            return $staff;
        });

        return redirect()->route('admin.staff.show', $staff)->with('success', 'Staff member saved.');
    }

    public function show(Staff $staff, DuesCalculationService $dues): View
    {
        $this->authorize('view', $staff);
        $year = (int) request('year', now()->year);

        return view('admin.staff.show', [
            'staff' => $staff->load('user'),
            'year' => $year,
            'matrix' => $dues->monthlyBreakdown($staff, $year),
            'payments' => DuesPayment::query()->with('recorder')->where('staff_id', $staff->id)->latest('payment_date')->paginate(20),
            'benefits' => Benefit::query()->with('benefitType')->where('staff_id', $staff->id)->latest()->get(),
            'requests' => BenefitRequest::query()->with('benefitType')->where('staff_id', $staff->id)->latest()->get(),
            'auditLogs' => AuditLog::query()
                ->where(function ($query) use ($staff) {
                    $query->where('auditable_type', Staff::class)->where('auditable_id', $staff->id);
                })
                ->latest()
                ->limit(20)
                ->get(),
            'summary' => [
                'dues_year' => $dues->totalPaid($staff, $year),
                'dues_all_time' => $dues->totalPaid($staff),
                'benefits_received' => (float) $staff->benefits()->where('status', Benefit::STATUS_PAID)->sum('amount'),
                'pending_benefits' => (float) $staff->benefits()->whereIn('status', [Benefit::STATUS_PENDING, Benefit::STATUS_APPROVED])->sum('amount'),
                'pending_requests' => $staff->benefitRequests()->whereIn('status', [BenefitRequest::STATUS_SUBMITTED, BenefitRequest::STATUS_UNDER_REVIEW])->count(),
            ],
        ]);
    }

    public function edit(Staff $staff): View
    {
        $this->authorize('update', $staff);

        return view('admin.staff.form', ['staff' => $staff]);
    }

    public function update(StoreStaffRequest $request, Staff $staff, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $staff);

        $old = $staff->toArray();
        $data = $request->safe()->except(['create_user', 'temporary_password']);
        $data['is_active'] = $request->boolean('is_active');
        $staff->update($data);
        $audit->log('staff_updated', $staff, $old, $staff->fresh()->toArray());

        return redirect()->route('admin.staff.show', $staff)->with('success', 'Staff member updated.');
    }

    public function createAccount(Request $request, Staff $staff, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $staff);
        $request->validate(['temporary_password' => ['required', 'string', 'min:8']]);

        if ($staff->user_id) {
            return back()->with('info', 'This staff member already has a login account.');
        }

        $this->createLoginAccount($staff, $request->input('temporary_password'));
        $audit->log('staff_login_created', $staff);

        return back()->with('success', 'Login account created for staff member.');
    }

    public function resetPassword(Request $request, Staff $staff, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $staff);
        $request->validate(['temporary_password' => ['required', 'string', 'min:8']]);

        abort_if(! $staff->user, 422, 'This staff member has no login account.');

        $staff->user->forceFill([
            'password' => Hash::make($request->input('temporary_password')),
            'must_change_password' => true,
        ])->save();

        $audit->log('staff_password_reset', $staff);

        return back()->with('success', 'Staff password reset.');
    }

    public function destroy(Request $request, Staff $staff, AuditService $audit): RedirectResponse
    {
        $this->authorize('delete', $staff);
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $old = $staff->toArray();
        $staff->update(['is_active' => false, 'notes' => trim(($staff->notes ? $staff->notes."\n" : '').'Deactivated: '.$request->input('reason'))]);
        $audit->log('staff_deactivated', $staff, $old, $staff->fresh()->toArray());

        return redirect()->route('admin.staff.index')->with('success', 'Staff member deactivated.');
    }

    public function requestDeletion(Request $request, Staff $staff, AuditService $audit): RedirectResponse
    {
        $this->authorize('delete', $staff);
        $validated = $request->validate(['reason' => ['required','string','max:500'], 'password' => ['required','current_password']]);
        if (Setting::value('system_mode', 'production') === 'debug') {
            $old = $staff->toArray();
            $this->deleteStaff($staff);
            $audit->log('staff_deleted_debug_mode', $staff, $old, ['reason'=>$validated['reason']], $request);
            return redirect()->route('admin.staff.index')->with('success', 'Staff record deleted immediately in Debug mode.');
        }
        if ($staff->deletionRequests()->where('status','pending')->exists()) return back()->withErrors(['reason'=>'A staff deletion request is already pending.']);
        $deletion = $staff->deletionRequests()->create(['requested_by'=>$request->user()->id,'reason'=>$validated['reason'],'status'=>'pending']);
        $audit->log('staff_deletion_requested', $staff, [], ['request_id'=>$deletion->id,'reason'=>$validated['reason']], $request);
        return back()->with('success', 'Staff deletion request submitted for second-admin approval.');
    }

    public function approveDeletion(Request $request, StaffDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending', 422);
        abort_if($deletionRequest->requested_by === $request->user()->id, 403, 'You cannot approve your own request.');
        $request->validate(['password'=>['required','current_password']]);
        $staff = $deletionRequest->staff; abort_if(!$staff || $staff->trashed(),422);
        $old=$staff->toArray(); $this->deleteStaff($staff);
        $deletionRequest->update(['status'=>'approved','reviewed_by'=>$request->user()->id,'reviewed_at'=>now()]);
        $audit->log('staff_deletion_approved',$staff,$old,['request_id'=>$deletionRequest->id],$request);
        return back()->with('success','Staff deletion approved and completed.');
    }

    public function rejectDeletion(Request $request, StaffDeletionRequest $deletionRequest, AuditService $audit): RedirectResponse
    {
        abort_unless($deletionRequest->status === 'pending',422); abort_if($deletionRequest->requested_by === $request->user()->id,403);
        $validated=$request->validate(['password'=>['required','current_password'],'review_notes'=>['nullable','string','max:500']]);
        $deletionRequest->update(['status'=>'rejected','reviewed_by'=>$request->user()->id,'reviewed_at'=>now(),'review_notes'=>$validated['review_notes']??null]);
        $audit->log('staff_deletion_rejected',$deletionRequest->staff,[],['request_id'=>$deletionRequest->id],$request);
        return back()->with('success','Staff deletion request rejected.');
    }

    private function deleteStaff(Staff $staff): void
    {
        DB::transaction(function () use ($staff) { if ($staff->user) $staff->user->update(['status'=>'inactive']); $staff->update(['is_active'=>false]); $staff->delete(); });
    }

    private function createLoginAccount(Staff $staff, string $password): User
    {
        $username = $staff->staff_id ?: 'staff'.$staff->id;

        $user = User::query()->create([
            'name' => $staff->full_name,
            'email' => $staff->email,
            'username' => $username,
            'password' => Hash::make($password),
            'status' => 'active',
            'must_change_password' => true,
        ]);
        $user->assignRole('Staff Member');
        $staff->update(['user_id' => $user->id]);

        return $user;
    }
}

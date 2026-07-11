<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\BenefitRequest;
use App\Services\DuesCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\AuditService;
use Illuminate\View\View;

class StaffPortalController extends Controller
{
    public function dashboard(Request $request, DuesCalculationService $dues): View
    {
        $staff = $request->user()->staff;
        abort_if(! $staff, 403, 'No staff profile is linked to this account.');
        $validated = $request->validate(['year' => ['nullable', 'integer', 'min:2000', 'max:2100']]);
        $year = (int) ($validated['year'] ?? now()->year);
        $paidYear = $dues->totalPaid($staff, $year);
        $expectedYear = $dues->annualExpected($year);
        $availableYears = $staff->duesPayments()->select('payment_year')->distinct()->pluck('payment_year')
            ->merge($staff->benefits()->selectRaw('YEAR(COALESCE(payment_date, created_at)) as benefit_year')->distinct()->pluck('benefit_year'))
            ->push(now()->year)->push($year)->filter()->unique()->sortDesc()->values();

        return view('staff.dashboard', [
            'staff' => $staff,
            'year' => $year,
            'availableYears' => $availableYears,
            'matrix' => $dues->monthlyBreakdown($staff, $year),
            'summary' => [
                'paid_year' => $paidYear,
                'expected_year' => $expectedYear,
                'outstanding' => max($expectedYear - $paidYear, 0),
                'credit' => max($paidYear - $expectedYear, 0),
                'benefits_received' => (float) $staff->benefits()->where('status', Benefit::STATUS_PAID)->whereYear('payment_date', $year)->sum('amount'),
                'pending_benefits' => (float) $staff->benefits()->whereIn('status', [Benefit::STATUS_PENDING, Benefit::STATUS_APPROVED])->whereYear('created_at', $year)->sum('amount'),
            ],
        ]);
    }

    public function dues(Request $request, DuesCalculationService $dues): View
    {
        $staff = $request->user()->staff;
        $year = (int) $request->integer('year', now()->year);

        return view('staff.dues', [
            'staff' => $staff,
            'year' => $year,
            'matrix' => $dues->monthlyBreakdown($staff, $year),
            'payments' => $staff->duesPayments()->with('recorder')->latest('payment_date')->paginate(20),
        ]);
    }

    public function benefits(Request $request): View
    {
        $staff = $request->user()->staff;

        return view('staff.benefits', [
            'benefits' => $staff->benefits()->with('benefitType')->latest()->paginate(20),
            'requests' => BenefitRequest::query()->with('benefitType')->where('staff_id', $staff->id)->latest()->limit(10)->get(),
        ]);
    }

    public function profile(Request $request): View
    {
        return view('staff.profile', ['staff' => $request->user()->staff]);
    }

    public function updateProfile(Request $request, AuditService $audit): RedirectResponse
    {
        $staff = $request->user()->staff;
        abort_if(! $staff, 403, 'No staff profile is linked to this account.');
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'gender' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:150'],
            'position' => ['nullable', 'string', 'max:150'],
            'employment_status' => ['nullable', 'string', 'max:100'],
            'date_joined' => ['nullable', 'date'],
            'association_joined_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $staff, $validated, $audit) {
            $old = $staff->toArray();
            $staff->update($validated);
            $request->user()->update(['name' => $validated['full_name'], 'email' => $validated['email'] ?: null]);
            $audit->log('staff_profile_updated', $staff, $old, $staff->fresh()->toArray(), $request);
        });

        return back()->with('success', 'Your profile has been updated.');
    }

    public function changePassword(): View
    {
        return view('staff.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        return back()->with('success', 'Password changed.');
    }
}

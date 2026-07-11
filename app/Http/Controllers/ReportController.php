<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\BenefitType;
use App\Models\DuesPayment;
use App\Models\Staff;
use App\Services\DuesCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function dues(Request $request, DuesCalculationService $dues): View
    {
        $year = (int) $request->integer('year', now()->year);
        $month = $request->filled('month') ? (int) $request->integer('month') : null;
        $staffRows = Staff::query()->active()
            ->when($request->filled('department'), fn ($query) => $query->where('department', $request->input('department')))
            ->orderBy('full_name')
            ->get();
        $expected = $month ? $dues->expectedForMonth($year, $month) : $dues->annualExpected($year);
        $paidByStaff = DuesPayment::query()
            ->where('payment_year', $year)
            ->when($month, fn ($query) => $query->where('payment_month', $month))
            ->selectRaw('staff_id, SUM(amount) as total_paid')
            ->groupBy('staff_id')
            ->pluck('total_paid', 'staff_id');

        $rows = $staffRows->map(function (Staff $staff) use ($dues, $expected, $paidByStaff) {
            $paid = (float) ($paidByStaff[$staff->id] ?? 0);

            return [
                'staff' => $staff,
                'expected' => $expected,
                'paid' => $paid,
                'balance' => max($expected - $paid, 0),
                'status' => $dues->status($expected, $paid),
            ];
        })->when($request->filled('status'), fn ($rows) => $rows->where('status', $request->input('status'))->values());

        return view('admin.reports.dues', [
            'rows' => $rows,
            'year' => $year,
            'month' => $month,
            'months' => DuesCalculationService::MONTHS,
            'departments' => Staff::query()->whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
        ]);
    }

    public function benefits(Request $request): View
    {
        $query = Benefit::query()->with(['staff', 'benefitType'])->latest();

        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->integer('year'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('benefit_type_id')) {
            $query->where('benefit_type_id', $request->integer('benefit_type_id'));
        }
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->integer('staff_id'));
        }

        return view('admin.reports.benefits', [
            'benefits' => $query->paginate(50)->withQueryString(),
            'benefitTypes' => BenefitType::query()->orderBy('name')->get(),
            'staff' => Staff::query()->orderBy('full_name')->get(),
        ]);
    }

    public function statement(Staff $staff, DuesCalculationService $dues): View
    {
        $this->authorize('view', $staff);
        $year = (int) request('year', now()->year);

        return view('admin.reports.statement', [
            'staff' => $staff,
            'year' => $year,
            'matrix' => $dues->monthlyBreakdown($staff, $year),
            'payments' => DuesPayment::query()->with('recorder')->where('staff_id', $staff->id)->latest('payment_date')->get(),
            'benefits' => Benefit::query()->with('benefitType')->where('staff_id', $staff->id)->latest()->get(),
        ]);
    }
}

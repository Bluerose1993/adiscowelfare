<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\BenefitType;
use App\Models\DuesPayment;
use App\Models\Staff;
use App\Services\DuesCalculationService;
use App\Services\BenefitExpenseReportService;
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

    public function benefits(Request $request, BenefitExpenseReportService $expenses): View
    {
        $filters = $this->benefitFilters($request);
        $query = $expenses->query($filters);

        return view('admin.reports.benefits', [
            'expenses' => (clone $query)->orderByDesc('expense_date')->orderByDesc('record_id')->paginate(50)->withQueryString(),
            'totalPaid' => (float) (clone $query)->sum('amount'),
            'filters' => $filters,
            'benefitTypes' => BenefitType::query()->orderBy('name')->get(),
            'staff' => Staff::query()->orderBy('full_name')->get(),
        ]);
    }

    public function printBenefits(Request $request, BenefitExpenseReportService $expenses): View
    {
        $filters = $this->benefitFilters($request);
        $query = $expenses->query($filters);

        return view('admin.reports.benefits-print', [
            'expenses' => (clone $query)->orderByDesc('expense_date')->orderByDesc('record_id')->get(),
            'totalPaid' => (float) (clone $query)->sum('amount'),
            'filters' => $filters,
        ]);
    }

    private function benefitFilters(Request $request): array
    {
        return $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'benefit_type_id' => ['nullable', 'integer', 'exists:benefit_types,id'],
            'staff_id' => ['nullable', 'integer', 'exists:staff,id'],
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

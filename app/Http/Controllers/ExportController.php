<?php

namespace App\Http\Controllers;

use App\Exports\AnnualDuesChartExport;
use App\Exports\AnnualBenefitsChartExport;
use App\Exports\BenefitsReportExport;
use App\Exports\DuesTransactionsExport;
use App\Exports\FinancialSummaryExport;
use App\Exports\StaffStatementExport;
use App\Models\Staff;
use App\Models\BenefitType;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function duesChart(Request $request, AuditService $audit): BinaryFileResponse
    {
        $year = (int) $request->integer('year', now()->year);
        $blank = $request->input('blank', '-');
        $audit->log('excel_export', null, [], ['type' => 'annual_dues_chart', 'year' => $year]);

        return Excel::download(new AnnualDuesChartExport($year, $blank), "annual-dues-chart-{$year}.xlsx");
    }

    public function transactions(Request $request, AuditService $audit): BinaryFileResponse
    {
        $year = $request->filled('year') ? (int) $request->integer('year') : null;
        $audit->log('excel_export', null, [], ['type' => 'dues_transactions', 'year' => $year]);

        return Excel::download(new DuesTransactionsExport($year), 'dues-transactions.xlsx');
    }

    public function staffStatement(Request $request, Staff $staff, AuditService $audit): BinaryFileResponse
    {
        $this->authorize('view', $staff);
        $year = (int) $request->integer('year', now()->year);
        $audit->log('excel_export', $staff, [], ['type' => 'staff_statement', 'year' => $year]);

        return Excel::download(new StaffStatementExport($staff, $year), "staff-statement-{$staff->id}-{$year}.xlsx");
    }

    public function benefits(Request $request, AuditService $audit): BinaryFileResponse
    {
        $year = (int) $request->integer('year', now()->year);
        $filters = $request->only(['status', 'benefit_type_id', 'staff_id']);
        if (! empty($filters['benefit_type_id'])) {
            $filters['benefit_type_name'] = BenefitType::query()->find($filters['benefit_type_id'])?->name;
        }
        $audit->log('excel_export', null, [], ['type' => 'annual_benefits_chart', 'year' => $year] + $filters);

        return Excel::download(new AnnualBenefitsChartExport($year, $filters), "annual-benefits-chart-{$year}.xlsx");
    }

    public function financialSummary(Request $request, AuditService $audit): BinaryFileResponse
    {
        $year = (int) $request->integer('year', now()->year);
        $audit->log('excel_export', null, [], ['type' => 'financial_summary', 'year' => $year]);

        return Excel::download(new FinancialSummaryExport($year), "dues-benefits-summary-{$year}.xlsx");
    }
}

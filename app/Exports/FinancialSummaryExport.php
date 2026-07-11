<?php

namespace App\Exports;

use App\Models\Benefit;
use App\Models\DuesPayment;
use Maatwebsite\Excel\Concerns\FromArray;

class FinancialSummaryExport implements FromArray
{
    public function __construct(private readonly int $year)
    {
    }

    public function array(): array
    {
        $rows = [['Month', 'Dues Collected', 'Benefits Paid', 'Net']];

        foreach (range(1, 12) as $month) {
            $dues = (float) DuesPayment::query()->where('payment_year', $this->year)->where('payment_month', $month)->sum('amount');
            $benefits = (float) Benefit::query()->where('status', Benefit::STATUS_PAID)->whereYear('payment_date', $this->year)->whereMonth('payment_date', $month)->sum('amount');
            $rows[] = [date('F', mktime(0, 0, 0, $month, 1)), $dues, $benefits, $dues - $benefits];
        }

        return $rows;
    }
}

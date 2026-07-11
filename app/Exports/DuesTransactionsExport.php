<?php

namespace App\Exports;

use App\Models\DuesPayment;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DuesTransactionsExport implements FromArray, WithHeadings
{
    public function __construct(private readonly ?int $year = null)
    {
    }

    public function headings(): array
    {
        return ['Date', 'Year', 'Month', 'Staff ID', 'Staff Name', 'Amount', 'Method', 'Reference', 'Recorded By'];
    }

    public function array(): array
    {
        return DuesPayment::query()
            ->with(['staff', 'recorder'])
            ->when($this->year, fn ($query) => $query->where('payment_year', $this->year))
            ->orderByDesc('payment_date')
            ->get()
            ->map(fn (DuesPayment $payment) => [
                $payment->payment_date?->toDateString(),
                $payment->payment_year,
                $payment->payment_month,
                $payment->staff?->staff_id,
                $payment->staff?->full_name,
                (float) $payment->amount,
                $payment->payment_method,
                $payment->reference_number,
                $payment->recorder?->name,
            ])
            ->all();
    }
}

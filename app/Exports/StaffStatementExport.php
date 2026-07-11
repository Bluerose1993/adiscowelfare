<?php

namespace App\Exports;

use App\Models\Staff;
use App\Services\DuesCalculationService;
use Maatwebsite\Excel\Concerns\FromArray;

class StaffStatementExport implements FromArray
{
    public function __construct(private readonly Staff $staff, private readonly int $year)
    {
    }

    public function array(): array
    {
        $dues = app(DuesCalculationService::class);
        $rows = [
            ['Staff Statement'],
            ['Staff ID', $this->staff->staff_id],
            ['Name', $this->staff->full_name],
            ['Department', $this->staff->department],
            [],
            ['Month', 'Expected', 'Paid', 'Balance', 'Status'],
        ];

        foreach ($dues->monthlyBreakdown($this->staff, $this->year) as $row) {
            $rows[] = [$row['month'], $row['expected'], $row['paid'], $row['balance'], $row['status']];
        }

        $rows[] = [];
        $rows[] = ['Benefits'];
        $rows[] = ['Type', 'Title', 'Status', 'Amount', 'Payment Date'];

        foreach ($this->staff->benefits()->with('benefitType')->latest()->get() as $benefit) {
            $rows[] = [$benefit->benefitType?->name, $benefit->title, $benefit->status, (float) $benefit->amount, $benefit->payment_date?->toDateString()];
        }

        return $rows;
    }
}

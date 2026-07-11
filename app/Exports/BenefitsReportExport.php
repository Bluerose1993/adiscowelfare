<?php

namespace App\Exports;

use App\Models\Benefit;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BenefitsReportExport implements FromArray, WithHeadings
{
    public function __construct(private readonly array $filters = [])
    {
    }

    public function headings(): array
    {
        return ['Staff ID', 'Staff Name', 'Benefit Type', 'Title', 'Status', 'Amount', 'Incident Date', 'Payment Date'];
    }

    public function array(): array
    {
        return Benefit::query()
            ->with(['staff', 'benefitType'])
            ->when($this->filters['year'] ?? null, fn ($query, $year) => $query->whereYear('created_at', $year))
            ->when($this->filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($this->filters['benefit_type_id'] ?? null, fn ($query, $type) => $query->where('benefit_type_id', $type))
            ->latest()
            ->get()
            ->map(fn (Benefit $benefit) => [
                $benefit->staff?->staff_id,
                $benefit->staff?->full_name,
                $benefit->benefitType?->name,
                $benefit->title,
                $benefit->status,
                (float) $benefit->amount,
                $benefit->incident_date?->toDateString(),
                $benefit->payment_date?->toDateString(),
            ])
            ->all();
    }
}

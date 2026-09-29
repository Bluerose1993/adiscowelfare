<?php

namespace App\Exports;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffLockersExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithCustomValueBinder, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly string $search, private readonly string $assignment)
    {
    }

    public function query(): Builder
    {
        return Staff::query()->search($this->search)->lockerAssignment($this->assignment)
            ->orderBy('full_name');
    }

    public function headings(): array
    {
        return ['Staff ID', 'Staff Name', 'Department', 'Locker Number'];
    }

    public function map($staff): array
    {
        return [
            (string) ($staff->staff_id ?? ''),
            $staff->full_name,
            $staff->department ?? '',
            $staff->locker_number ?? '',
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), ['A', 'D'], true)) {
            $cell->setValueExplicit((string) ($value ?? ''), DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:D'.$sheet->getHighestRow());

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '17365D']]]];
    }
}

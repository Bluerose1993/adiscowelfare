<?php

namespace App\Exports;

use App\Models\Benefit;
use App\Models\Setting;
use App\Models\Staff;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class AnnualBenefitsChartExport implements FromArray, WithEvents, WithProperties, WithTitle
{
    public function __construct(private readonly int $year, private readonly array $filters = [], private readonly string $blank = '-')
    {
    }

    public function array(): array
    {
        $typeName = $this->filters['benefit_type_name'] ?? null;
        $status = ($this->filters['status'] ?? null) ?: Benefit::STATUS_PAID;
        $rows = [
            [Setting::value('association_name', 'ADISADEL COLLEGE TEACHING STAFF WELFARE ASSOCIATION')],
            ['JANUARY-DECEMBER, '.$this->year.' BENEFITS RECEIVED CHART'.($typeName ? ' - '.strtoupper($typeName) : '')],
            ['Generated: '.now()->format('Y-m-d H:i')],
            ['No.', 'Staff ID', 'Names of Members', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEPT', 'OCT', 'NOV', 'DEC', 'TOTAL BENEFITS'],
        ];

        Staff::query()
            ->when($this->filters['staff_id'] ?? null, fn ($query, $staffId) => $query->whereKey($staffId))
            ->orderBy('full_name')->chunk(200, function ($staffMembers) use (&$rows, $status) {
            foreach ($staffMembers as $staff) {
                $monthly = Benefit::query()
                    ->where('staff_id', $staff->id)
                    ->where('status', $status)
                    ->when($this->filters['benefit_type_id'] ?? null, fn ($query, $type) => $query->where('benefit_type_id', $type))
                    ->whereYear('payment_date', $this->year)
                    ->selectRaw('MONTH(payment_date) as month_number, SUM(amount) as total')
                    ->groupByRaw('MONTH(payment_date)')
                    ->pluck('total', 'month_number');

                $line = [count($rows) - 3, $staff->staff_id, $staff->full_name];
                $total = 0.0;
                foreach (range(1, 12) as $month) {
                    $amount = (float) ($monthly[$month] ?? 0);
                    $total += $amount;
                    $line[] = $amount > 0 ? $amount : $this->blank;
                }
                $line[] = $total > 0 ? $total : $this->blank;
                $rows[] = $line;
            }
        });

        return $rows;
    }

    public function title(): string
    {
        return 'Benefits '.$this->year;
    }

    public function properties(): array
    {
        return ['creator' => config('app.name'), 'title' => 'Annual Benefits Chart '.$this->year, 'subject' => 'Benefits received'];
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $lastRow = max($sheet->getHighestRow(), 4);
            foreach (['A1:P1', 'A2:P2', 'A3:P3'] as $range) {
                $sheet->mergeCells($range);
            }
            $sheet->getStyle('A1:P2')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
            $sheet->getStyle('A1:P2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
            $sheet->getStyle('A3:P3')->getFont()->setItalic(true)->getColor()->setRGB('64748B');
            $sheet->getStyle('A4:P4')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
            $sheet->getStyle('A4:P4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F172A');
            $sheet->getStyle('A1:P4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A4:P{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
            $sheet->getStyle("D5:P{$lastRow}")->getNumberFormat()->setFormatCode('₵#,##0.00');
            $sheet->getStyle("A5:C{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("D5:P{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getRowDimension(1)->setRowHeight(26);
            $sheet->getRowDimension(2)->setRowHeight(24);
            $sheet->getRowDimension(4)->setRowHeight(24);
            $sheet->getColumnDimension('A')->setWidth(7);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(34);
            foreach (range('D', 'O') as $column) {
                $sheet->getColumnDimension($column)->setWidth(12);
            }
            $sheet->getColumnDimension('P')->setWidth(17);
            $sheet->freezePane('D5');
            $sheet->setAutoFilter("A4:P{$lastRow}");
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(0)->setRowsToRepeatAtTopByStartAndEnd(1, 4)->setPrintArea("A1:P{$lastRow}");
        }];
    }
}

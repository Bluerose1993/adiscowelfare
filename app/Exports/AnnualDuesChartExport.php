<?php

namespace App\Exports;

use App\Models\Setting;
use App\Models\Staff;
use App\Services\DuesCalculationService;
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

class AnnualDuesChartExport implements FromArray, WithEvents, WithProperties, WithTitle
{
    public function __construct(private readonly int $year, private readonly string $blank = '-')
    {
    }

    public function array(): array
    {
        $dues = app(DuesCalculationService::class);
        $rows = [
            [Setting::value('association_name', 'ADISADEL COLLEGE TEACHING STAFF WELFARE ASSOCIATION')],
            ['JANUARY-DECEMBER, '.$this->year.' DUES PAYMENT CHART'],
            ['Generated: '.now()->format('Y-m-d H:i')],
            ['No.', 'Staff ID', 'Names of Members', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEPT', 'OCT', 'NOV', 'DEC', 'TOTAL PAYMENT'],
        ];

        Staff::query()->orderBy('full_name')->chunk(200, function ($staffMembers) use (&$rows, $dues) {
            foreach ($staffMembers as $staff) {
                $monthly = $dues->paidByMonth($staff, $this->year);
                $line = [count($rows) - 3, $staff->staff_id, $staff->full_name];
                $total = 0.0;

                foreach (range(1, 12) as $month) {
                    $paid = (float) ($monthly[$month] ?? 0);
                    $total += $paid;
                    $line[] = $paid > 0 ? $paid : $this->blank;
                }

                $line[] = $total > 0 ? $total : $this->blank;
                $rows[] = $line;
            }
        });

        return $rows;
    }

    public function title(): string
    {
        return 'Annual Dues '.$this->year;
    }

    public function properties(): array
    {
        return [
            'creator' => config('app.name'),
            'title' => 'Annual Dues Chart '.$this->year,
            'subject' => 'Welfare dues',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max($sheet->getHighestRow(), 4);
                $sheet->mergeCells('A1:P1');
                $sheet->mergeCells('A2:P2');
                $sheet->mergeCells('A3:P3');
                $sheet->getStyle('A1:P2')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
                $sheet->getStyle('A1:P2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
                $sheet->getStyle('A3:P3')->getFont()->setItalic(true)->getColor()->setRGB('64748B');
                $sheet->getStyle('A4:P4')->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
                $sheet->getStyle('A4:P4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F172A');
                $sheet->getStyle('A1:P4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A4:P{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
                $sheet->getStyle("D5:P{$lastRow}")->getNumberFormat()->setFormatCode('₵#,##0.00');
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
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0)
                    ->setRowsToRepeatAtTopByStartAndEnd(1, 4)
                    ->setPrintArea("A1:P{$lastRow}");
            },
        ];
    }
}

<?php

namespace App\Services;

use App\Models\DuesPayment;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelImportService
{
    public function __construct(private readonly StaffMatchingService $matcher)
    {
    }

    public function preview(UploadedFile $file, int $year, int $userId): ImportBatch
    {
        $path = $file->store('imports');
        $spreadsheet = IOFactory::load(Storage::path($path));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        [$headerRow, $columns] = $this->detectColumns($rows);
        $detectedYear = $this->detectYear($rows);

        if ($detectedYear && $detectedYear !== $year) {
            throw new \RuntimeException("The workbook title indicates {$detectedYear}, but the selected import year is {$year}.");
        }

        return DB::transaction(function () use ($file, $path, $userId, $rows, $headerRow, $columns, $year) {
            $batch = ImportBatch::query()->create([
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'uploaded_by' => $userId,
                'status' => 'previewed',
                'summary' => ['year' => $year],
            ]);

            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber <= $headerRow) {
                    continue;
                }

                $name = trim((string) ($row[$columns['name']] ?? ''));
                $staffId = isset($columns['staff_id']) ? trim((string) ($row[$columns['staff_id']] ?? '')) : null;

                if ($name === '' && $staffId === '') {
                    continue;
                }

                $monthly = [];
                foreach ($columns['months'] as $month => $column) {
                    $monthly[$month] = $this->money($row[$column] ?? null);
                }

                $match = $this->matcher->match($staffId, $name);

                ImportBatchRow::query()->create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'staff_id' => $staffId ?: null,
                    'full_name' => $name ?: null,
                    'monthly_amounts' => $monthly,
                    'reported_total' => isset($columns['total']) ? $this->money($row[$columns['total']] ?? null) : array_sum($monthly),
                    'matched_staff_id' => $match['staff']?->id,
                    'status' => $match['status'],
                    'message' => $match['message'].' Import year: '.$year,
                ]);
            }

            $batch->update([
                'manual_review_count' => $batch->rows()->where('status', 'manual_review')->count(),
                'summary' => ['year' => $year, 'header_row' => $headerRow],
            ]);

            return $batch;
        });
    }

    public function commit(ImportBatch $batch, int $userId): array
    {
        $year = (int) ($batch->summary['year'] ?? now()->year);
        $summary = [
            'staff_created' => 0,
            'staff_matched' => 0,
            'payments_created' => 0,
            'duplicates_skipped' => 0,
            'manual_review_count' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($batch, $year, $userId, &$summary) {
            foreach ($batch->rows as $row) {
                if ($row->status === 'manual_review') {
                    $summary['manual_review_count']++;
                    continue;
                }

                $staff = $row->matchedStaff;
                if (! $staff) {
                    $staff = Staff::query()->create([
                        'staff_id' => $row->staff_id,
                        'full_name' => $row->full_name ?: 'Unknown staff '.$row->row_number,
                        'is_active' => true,
                        'notes' => 'Created from import batch '.$batch->id,
                    ]);
                    $row->update(['matched_staff_id' => $staff->id, 'status' => 'created']);
                    $summary['staff_created']++;
                } else {
                    $summary['staff_matched']++;
                }

                foreach ($row->monthly_amounts ?? [] as $month => $amount) {
                    $amount = (float) $amount;
                    if ($amount <= 0) {
                        continue;
                    }

                    $existingTotal = (float) DuesPayment::query()
                        ->where('staff_id', $staff->id)
                        ->where('payment_year', $year)
                        ->where('payment_month', (int) $month)
                        ->sum('amount');

                    if ($existingTotal >= $amount) {
                        $summary['duplicates_skipped']++;
                        continue;
                    }

                    DuesPayment::query()->create([
                        'staff_id' => $staff->id,
                        'payment_year' => $year,
                        'payment_month' => (int) $month,
                        'amount' => $amount - $existingTotal,
                        'payment_date' => now()->setDate($year, (int) $month, 1)->endOfMonth()->toDateString(),
                        'payment_method' => 'Historic import',
                        'reference_number' => 'IMPORT-'.$batch->id.'-'.$row->row_number.'-'.$month,
                        'notes' => 'Imported from '.$batch->original_filename,
                        'recorded_by' => $userId,
                    ]);
                    $summary['payments_created']++;
                }
            }

            $unresolved = $batch->rows()->where('status', 'manual_review')->count();
            $batch->update([
                'status' => $unresolved > 0 ? 'review_required' : 'committed',
                'staff_created' => $summary['staff_created'],
                'staff_matched' => $summary['staff_matched'],
                'payments_created' => $summary['payments_created'],
                'duplicates_skipped' => $summary['duplicates_skipped'],
                'manual_review_count' => $unresolved,
                'summary' => array_merge($batch->summary ?? [], $summary),
            ]);
        });

        return $summary;
    }

    private function detectColumns(array $rows): array
    {
        $monthNames = [
            1 => ['jan', 'january'],
            2 => ['feb', 'february'],
            3 => ['mar', 'march'],
            4 => ['apr', 'april'],
            5 => ['may'],
            6 => ['jun', 'june'],
            7 => ['jul', 'july'],
            8 => ['aug', 'august'],
            9 => ['sep', 'sept', 'september'],
            10 => ['oct', 'october'],
            11 => ['nov', 'november'],
            12 => ['dec', 'december'],
        ];

        foreach (array_slice($rows, 0, 30, true) as $rowNumber => $row) {
            $columns = ['months' => []];
            foreach ($row as $column => $value) {
                $label = strtolower(trim((string) $value));
                $label = preg_replace('/[^a-z0-9 ]/', '', $label) ?? '';

                if (in_array($label, ['staff id', 'staffid', 'id number'], true)) {
                    $columns['staff_id'] = $column;
                }
                if (str_contains($label, 'name')) {
                    $columns['name'] = $column;
                }
                if (str_contains($label, 'total')) {
                    $columns['total'] = $column;
                }
                foreach ($monthNames as $month => $labels) {
                    if (in_array($label, $labels, true)) {
                        $columns['months'][$month] = $column;
                    }
                }
            }

            if (isset($columns['name']) && count($columns['months']) >= 6) {
                return [$rowNumber, $columns];
            }
        }

        throw new \RuntimeException('Could not identify the staff name and month columns in the uploaded workbook.');
    }

    private function money(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return $clean === '' ? 0.0 : (float) $clean;
    }

    private function detectYear(array $rows): ?int
    {
        foreach (array_slice($rows, 0, 5, true) as $row) {
            foreach ($row as $value) {
                if (preg_match('/\b(20\d{2})\b/', (string) $value, $matches)) {
                    return (int) $matches[1];
                }
            }
        }

        return null;
    }
}

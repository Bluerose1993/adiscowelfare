<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;

class StaffExcelImportService
{
    public function import(UploadedFile $file): array
    {
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        [$headerRow, $columns] = $this->detectColumns($rows);
        $staffIdCounts = [];
        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber > $headerRow) {
                $candidate = $this->identifier($row[$columns['staff_id']] ?? null);
                if ($candidate !== '') {
                    $staffIdCounts[$candidate] = ($staffIdCounts[$candidate] ?? 0) + 1;
                }
            }
        }

        $summary = [
            'processed' => 0,
            'staff_created' => 0,
            'staff_updated' => 0,
            'accounts_created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber <= $headerRow) {
                continue;
            }

            $name = trim((string) ($row[$columns['name']] ?? ''));
            $staffId = $this->identifier($row[$columns['staff_id']] ?? null);
            $phone = $this->phone($row[$columns['phone']] ?? null);

            if ($name === '' && $staffId === '' && $phone === '') {
                continue;
            }

            $summary['processed']++;

            if ($name === '' || $staffId === '' || $phone === '') {
                $summary['skipped']++;
                $summary['errors'][] = "Row {$rowNumber}: name, staff ID and phone number are required.";
                continue;
            }

            if (! ctype_digit($staffId)) {
                $summary['skipped']++;
                $summary['errors'][] = "Row {$rowNumber}: staff ID '{$staffId}' is not a valid numeric staff ID.";
                continue;
            }

            if (($staffIdCounts[$staffId] ?? 0) > 1) {
                $summary['skipped']++;
                $summary['errors'][] = "Row {$rowNumber}: staff ID {$staffId} occurs more than once in the workbook.";
                continue;
            }

            if (strlen($phone) < 8) {
                $summary['skipped']++;
                $summary['errors'][] = "Row {$rowNumber}: phone number is too short to be used as a temporary password.";
                continue;
            }

            try {
                $result = DB::transaction(function () use ($name, $staffId, $phone) {
                    $staff = Staff::withTrashed()->where('staff_id', $staffId)->first();
                    $staffResult = 'updated';
                    $accountCreated = false;

                    if ($staff) {
                        $staff->restore();
                        $staff->update([
                            'full_name' => $name,
                            'phone' => $phone,
                            'is_active' => true,
                        ]);
                    } else {
                        $staff = Staff::create([
                            'staff_id' => $staffId,
                            'full_name' => $name,
                            'phone' => $phone,
                            'is_active' => true,
                            'notes' => 'Created from staff initialization workbook.',
                        ]);
                        $staffResult = 'created';
                    }

                    if (! $staff->user_id) {
                        if (User::where('username', $staffId)->exists()) {
                            throw new \RuntimeException("username {$staffId} is already assigned to another account");
                        }

                        $user = User::create([
                            'name' => $name,
                            'username' => $staffId,
                            'password' => Hash::make($phone),
                            'status' => 'active',
                            'must_change_password' => true,
                        ]);
                        $user->assignRole(Role::findByName('Staff Member'));
                        $staff->update(['user_id' => $user->id]);
                        $accountCreated = true;
                    }

                    return compact('staffResult', 'accountCreated');
                });

                $summary['staff_'.$result['staffResult']]++;
                if ($result['accountCreated']) {
                    $summary['accounts_created']++;
                }
            } catch (\Throwable $exception) {
                $summary['skipped']++;
                $summary['errors'][] = "Row {$rowNumber} ({$staffId}): {$exception->getMessage()}";
            }
        }

        return $summary;
    }

    private function detectColumns(array $rows): array
    {
        foreach (array_slice($rows, 0, 20, true) as $rowNumber => $row) {
            $columns = [];
            foreach ($row as $column => $value) {
                $label = strtolower(trim((string) $value));
                $label = preg_replace('/[^a-z0-9]+/', ' ', $label) ?? '';
                $label = trim($label);

                if (in_array($label, ['name', 'full name', 'staff name'], true)) {
                    $columns['name'] = $column;
                } elseif (in_array($label, ['staff id', 'staff number', 'staff no', 'employee id'], true)) {
                    $columns['staff_id'] = $column;
                } elseif (in_array($label, ['phone', 'phone number', 'telephone', 'mobile', 'mobile number'], true)) {
                    $columns['phone'] = $column;
                }
            }

            if (isset($columns['name'], $columns['staff_id'], $columns['phone'])) {
                return [$rowNumber, $columns];
            }
        }

        throw new \RuntimeException('Could not find NAME, STAFF ID and PHONE NUMBER columns in the workbook.');
    }

    private function identifier(mixed $value): string
    {
        $value = trim((string) $value);

        return preg_match('/^\d+\.0+$/', $value) ? strstr($value, '.', true) : $value;
    }

    private function phone(mixed $value): string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $value)) ?? '';
        if (strlen($digits) === 9) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}

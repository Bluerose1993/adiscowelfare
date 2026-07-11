<?php

namespace App\Services;

use App\Models\Staff;

class StaffMatchingService
{
    public function normalizeName(?string $name): string
    {
        $name = strtolower(trim((string) $name));
        $name = preg_replace('/\b(mr|mrs|miss|ms|rev|reverend|fr|father|canon|apostle|aps|dr)\b\.?/i', ' ', $name) ?? '';
        $name = preg_replace('/[^a-z0-9 ]+/', ' ', $name) ?? '';
        $name = preg_replace('/\s+/', ' ', $name) ?? '';

        return trim($name);
    }

    public function match(?string $staffId, ?string $fullName): array
    {
        if ($staffId) {
            $staff = Staff::query()->where('staff_id', $staffId)->first();
            if ($staff) {
                return ['status' => 'matched', 'staff' => $staff, 'message' => 'Matched by staff ID.'];
            }
        }

        $normalized = $this->normalizeName($fullName);
        if ($normalized === '') {
            return ['status' => 'manual_review', 'staff' => null, 'message' => 'Missing name and staff ID.'];
        }

        $matches = Staff::query()->get()
            ->filter(fn (Staff $staff) => $this->normalizeName($staff->full_name) === $normalized)
            ->values();

        if ($matches->count() === 1) {
            return ['status' => 'matched', 'staff' => $matches->first(), 'message' => 'Matched by exact normalized name.'];
        }

        if ($matches->count() > 1) {
            return ['status' => 'manual_review', 'staff' => null, 'message' => 'Multiple exact name matches require manual review.'];
        }

        $signature = $this->nameSignature($normalized);
        $signatureMatches = Staff::query()->get()
            ->filter(fn (Staff $staff) => $this->nameSignature($this->normalizeName($staff->full_name)) === $signature)
            ->values();

        if ($signatureMatches->count() === 1) {
            return ['status' => 'matched', 'staff' => $signatureMatches->first(), 'message' => 'Matched by unique name components.'];
        }

        if ($signatureMatches->count() > 1) {
            return ['status' => 'manual_review', 'staff' => null, 'message' => 'Multiple staff share these name components; manual review is required.'];
        }

        return ['status' => 'manual_review', 'staff' => null, 'message' => 'No safe staff match found. Import staff first or correct the name.'];
    }

    private function nameSignature(string $name): string
    {
        $parts = array_values(array_filter(explode(' ', $name)));
        sort($parts);

        return implode(' ', $parts);
    }
}

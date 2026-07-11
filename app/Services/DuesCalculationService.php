<?php

namespace App\Services;

use App\Models\DuesPayment;
use App\Models\DuesRate;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DuesCalculationService
{
    public const MONTHS = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    public function expectedForMonth(int $year, int $month): float
    {
        $date = CarbonImmutable::create($year, $month, 1)->endOfMonth();

        $rate = DuesRate::query()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        return (float) ($rate?->amount ?? 0);
    }

    public function annualExpected(int $year): float
    {
        return collect(array_keys(self::MONTHS))
            ->sum(fn (int $month) => $this->expectedForMonth($year, $month));
    }

    public function paidByMonth(Staff $staff, int $year): Collection
    {
        return DuesPayment::query()
            ->where('staff_id', $staff->id)
            ->where('payment_year', $year)
            ->selectRaw('payment_month, SUM(amount) as paid')
            ->groupBy('payment_month')
            ->pluck('paid', 'payment_month')
            ->map(fn ($amount) => (float) $amount);
    }

    public function monthlyBreakdown(Staff $staff, int $year): array
    {
        $paidByMonth = $this->paidByMonth($staff, $year);
        $rows = [];

        foreach (self::MONTHS as $number => $name) {
            $expected = $this->expectedForMonth($year, $number);
            $paid = (float) ($paidByMonth[$number] ?? 0);
            $balance = max($expected - $paid, 0);

            $rows[$number] = [
                'month' => $name,
                'expected' => $expected,
                'paid' => $paid,
                'balance' => $balance,
                'status' => $this->status($expected, $paid),
            ];
        }

        return $rows;
    }

    public function status(float $expected, float $paid): string
    {
        if ($expected <= 0 && $paid <= 0) {
            return 'unconfigured';
        }

        if ($paid <= 0) {
            return 'unpaid';
        }

        if ($paid < $expected) {
            return 'partially_paid';
        }

        if ($paid > $expected) {
            return 'overpaid';
        }

        return 'paid';
    }

    public function totalPaid(?Staff $staff = null, ?int $year = null, ?int $month = null): float
    {
        $query = DuesPayment::query();

        if ($staff) {
            $query->where('staff_id', $staff->id);
        }

        if ($year) {
            $query->where('payment_year', $year);
        }

        if ($month) {
            $query->where('payment_month', $month);
        }

        return (float) $query->sum('amount');
    }
}

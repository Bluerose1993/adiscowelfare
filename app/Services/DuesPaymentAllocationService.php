<?php

namespace App\Services;

use App\Models\DuesPayment;
use Carbon\CarbonImmutable;

class DuesPaymentAllocationService
{
    public function __construct(private readonly DuesCalculationService $dues)
    {
    }

    public function record(array $data, int $userId): array
    {
        $remaining = round((float) $data['amount'], 2);
        $cursor = CarbonImmutable::create((int) $data['payment_year'], (int) $data['payment_month'], 1);
        $payments = [];

        for ($step = 0; $remaining > 0 && $step < 120; $step++, $cursor = $cursor->addMonth()) {
            $expected = $this->dues->expectedForMonth($cursor->year, $cursor->month);
            $alreadyPaid = (float) DuesPayment::query()
                ->where('staff_id', $data['staff_id'])
                ->where('payment_year', $cursor->year)
                ->where('payment_month', $cursor->month)
                ->sum('amount');
            $outstanding = max(round($expected - $alreadyPaid, 2), 0);

            if ($expected <= 0) {
                $outstanding = $remaining;
            } elseif ($outstanding <= 0) {
                continue;
            }

            $allocated = min($remaining, $outstanding);
            $payments[] = DuesPayment::query()->create([
                'staff_id' => $data['staff_id'],
                'payment_year' => $cursor->year,
                'payment_month' => $cursor->month,
                'amount' => $allocated,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $this->allocationNote($data['notes'] ?? null, $cursor, $data),
                'recorded_by' => $userId,
            ]);
            $remaining = round($remaining - $allocated, 2);
        }

        if ($remaining > 0 && $payments) {
            $last = end($payments);
            $last->increment('amount', $remaining);
            $last->refresh();
        }

        return $payments;
    }

    private function allocationNote(?string $note, CarbonImmutable $allocatedMonth, array $data): ?string
    {
        $startingMonth = CarbonImmutable::create((int) $data['payment_year'], (int) $data['payment_month'], 1);
        if ($allocatedMonth->isSameMonth($startingMonth)) {
            return $note;
        }

        return trim(($note ? $note.' ' : '').'Automatically rolled over from '.$startingMonth->format('F Y').'.');
    }
}

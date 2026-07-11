<?php

namespace App\Services;

use App\Models\Benefit;
use App\Models\BenefitRequest;
use Illuminate\Support\Facades\DB;

class BenefitService
{
    public function approveRequest(BenefitRequest $request, float $amount, ?string $notes, int $userId): Benefit
    {
        return DB::transaction(function () use ($request, $amount, $notes, $userId) {
            $request->refresh();

            if ($request->resulting_benefit_id) {
                return $request->resultingBenefit;
            }

            $benefit = Benefit::query()->create([
                'staff_id' => $request->staff_id,
                'benefit_type_id' => $request->benefit_type_id,
                'title' => $request->subject,
                'description' => $request->description,
                'amount' => $amount,
                'incident_date' => $request->incident_date,
                'approved_date' => now()->toDateString(),
                'status' => Benefit::STATUS_PENDING,
                'created_by' => $userId,
                'approved_by' => $userId,
                'notes' => $notes,
            ]);

            $request->update([
                'status' => BenefitRequest::STATUS_APPROVED,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
                'review_notes' => $notes,
                'resulting_benefit_id' => $benefit->id,
            ]);

            return $benefit;
        });
    }
}

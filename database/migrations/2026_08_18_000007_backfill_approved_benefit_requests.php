<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $fallbackUserId = DB::table('users')->orderBy('id')->value('id');
        if (! $fallbackUserId) return;

        DB::table('benefit_requests')
            ->where('status', 'approved')
            ->orderBy('id')
            ->chunkById(100, function ($requests) use ($fallbackUserId) {
                foreach ($requests as $request) {
                    $amount = $request->approved_amount ?? $request->requested_amount;
                    if ($amount === null) continue;

                    if ($request->resulting_benefit_id) {
                        DB::table('benefits')->where('id', $request->resulting_benefit_id)->update([
                            'amount' => $amount,
                            'status' => 'approved',
                            'approved_by' => $request->reviewed_by ?: $fallbackUserId,
                            'approved_date' => $request->reviewed_at ? date('Y-m-d', strtotime($request->reviewed_at)) : now()->toDateString(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        $benefitId = DB::table('benefits')->insertGetId([
                            'staff_id' => $request->staff_id,
                            'benefit_type_id' => $request->benefit_type_id,
                            'title' => $request->subject,
                            'description' => $request->description,
                            'amount' => $amount,
                            'incident_date' => $request->incident_date,
                            'approved_date' => $request->reviewed_at ? date('Y-m-d', strtotime($request->reviewed_at)) : now()->toDateString(),
                            'status' => 'approved',
                            'created_by' => $request->reviewed_by ?: $fallbackUserId,
                            'approved_by' => $request->reviewed_by ?: $fallbackUserId,
                            'notes' => $request->review_notes,
                            'created_at' => $request->reviewed_at ?: now(),
                            'updated_at' => now(),
                        ]);
                        DB::table('benefit_requests')->where('id', $request->id)->update([
                            'resulting_benefit_id' => $benefitId,
                            'approved_amount' => $amount,
                            'updated_at' => now(),
                        ]);
                    }

                    if ($request->approved_amount === null) {
                        DB::table('benefit_requests')->where('id', $request->id)->update(['approved_amount' => $amount, 'updated_at' => now()]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Historical financial records are intentionally not removed on rollback.
    }
};

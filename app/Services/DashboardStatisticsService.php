<?php

namespace App\Services;

use App\Models\Benefit;
use App\Models\BenefitRequest;
use App\Models\DuesPayment;
use App\Models\Staff;

class DashboardStatisticsService
{
    public function adminStats(int $year): array
    {
        $month = (int) now()->month;

        return [
            'active_staff' => Staff::query()->active()->count(),
            'dues_this_month' => (float) DuesPayment::query()->where('payment_year', $year)->where('payment_month', $month)->sum('amount'),
            'dues_this_year' => (float) DuesPayment::query()->where('payment_year', $year)->sum('amount'),
            'dues_all_time' => (float) DuesPayment::query()->sum('amount'),
            'benefits_paid_year' => (float) Benefit::query()->where('status', Benefit::STATUS_PAID)->whereYear('payment_date', $year)->sum('amount'),
            'benefits_paid_all_time' => (float) Benefit::query()->where('status', Benefit::STATUS_PAID)->sum('amount'),
            'pending_benefit_amount' => (float) Benefit::query()->whereIn('status', [Benefit::STATUS_PENDING, Benefit::STATUS_APPROVED])->sum('amount'),
            'pending_requests' => BenefitRequest::query()->whereIn('status', [BenefitRequest::STATUS_SUBMITTED, BenefitRequest::STATUS_UNDER_REVIEW])->count(),
        ];
    }

    public function monthlyDues(int $year): array
    {
        $payments = DuesPayment::query()
            ->where('payment_year', $year)
            ->selectRaw('payment_month, SUM(amount) as total')
            ->groupBy('payment_month')
            ->pluck('total', 'payment_month');

        return array_map(fn (int $month) => (float) ($payments[$month] ?? 0), range(1, 12));
    }

    public function benefitDistribution(int $year): array
    {
        return Benefit::query()
            ->join('benefit_types', 'benefit_types.id', '=', 'benefits.benefit_type_id')
            ->whereYear('benefits.created_at', $year)
            ->selectRaw('benefit_types.name, SUM(benefits.amount) as total')
            ->groupBy('benefit_types.name')
            ->orderBy('benefit_types.name')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'total' => (float) $row->total])
            ->all();
    }
}

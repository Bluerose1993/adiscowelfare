<?php

namespace App\Http\Controllers;

use App\Models\Benefit;
use App\Models\BenefitRequest;
use App\Models\DuesPayment;
use App\Services\DashboardStatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardStatisticsService $statistics): View
    {
        $year = (int) $request->integer('year', (int) now()->year);

        return view('admin.dashboard', [
            'year' => $year,
            'stats' => $statistics->adminStats($year),
            'monthlyDues' => $statistics->monthlyDues($year),
            'benefitDistribution' => $statistics->benefitDistribution($year),
            'recentPayments' => DuesPayment::query()->with(['staff', 'recorder'])->latest()->limit(8)->get(),
            'recentBenefits' => Benefit::query()->with(['staff', 'benefitType'])->where('status', Benefit::STATUS_PAID)->latest('payment_date')->limit(8)->get(),
            'newRequests' => BenefitRequest::query()->with(['staff', 'benefitType'])->where('status', BenefitRequest::STATUS_SUBMITTED)->latest()->limit(8)->get(),
            'pendingBenefits' => Benefit::query()->with(['staff', 'benefitType'])->whereIn('status', [Benefit::STATUS_PENDING, Benefit::STATUS_APPROVED])->latest()->limit(8)->get(),
        ]);
    }
}

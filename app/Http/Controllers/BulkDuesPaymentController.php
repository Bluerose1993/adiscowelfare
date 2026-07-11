<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDuesPaymentRequest;
use App\Models\DuesPayment;
use App\Models\Staff;
use App\Services\AuditService;
use App\Services\DuesCalculationService;
use App\Services\DuesPaymentAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BulkDuesPaymentController extends Controller
{
    public function create(Request $request, DuesCalculationService $dues): View
    {
        $this->authorize('create', DuesPayment::class);
        $year = (int) $request->integer('payment_year', now()->year);
        $month = (int) $request->integer('payment_month', now()->month);

        return view('admin.dues.bulk', [
            'staff' => Staff::query()->active()->orderBy('full_name')->get(),
            'months' => DuesCalculationService::MONTHS,
            'year' => $year,
            'month' => $month,
            'expected' => $dues->expectedForMonth($year, $month),
            'paidByStaff' => DuesPayment::query()
                ->where('payment_year', $year)
                ->where('payment_month', $month)
                ->selectRaw('staff_id, SUM(amount) as paid')
                ->groupBy('staff_id')
                ->pluck('paid', 'staff_id'),
        ]);
    }

    public function store(BulkDuesPaymentRequest $request, AuditService $audit, DuesPaymentAllocationService $allocator): RedirectResponse
    {
        $created = 0;

        DB::transaction(function () use ($request, $audit, $allocator, &$created) {
            foreach ($request->validated('payments') as $row) {
                if (blank($row['amount'] ?? null)) {
                    continue;
                }

                $payments = $allocator->record([
                    'staff_id' => $row['staff_id'],
                    'payment_year' => $request->integer('payment_year'),
                    'payment_month' => $request->integer('payment_month'),
                    'amount' => $row['amount'],
                    'payment_date' => $row['payment_date'] ?? now()->toDateString(),
                    'reference_number' => $row['reference_number'] ?? null,
                ], $request->user()->id);
                foreach ($payments as $payment) {
                    $created++;
                    $audit->log('dues_payment_created', $payment, [], $payment->toArray());
                }
            }
        });

        return back()->with('success', "{$created} payment(s) recorded.");
    }
}

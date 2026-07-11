<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBenefitRequest;
use App\Models\Benefit;
use App\Models\BenefitType;
use App\Models\Staff;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BenefitController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Benefit::class);

        $query = Benefit::query()->with(['staff', 'benefitType'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.benefits.index', [
            'benefits' => $query->paginate(50)->withQueryString(),
            'status' => $request->input('status'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Benefit::class);

        return view('admin.benefits.form', [
            'benefit' => new Benefit(['status' => Benefit::STATUS_PENDING]),
            'staff' => Staff::query()->active()->orderBy('full_name')->get(),
            'benefitTypes' => BenefitType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBenefitRequest $request, AuditService $audit): RedirectResponse
    {
        $benefit = Benefit::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'approved_by' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? $request->user()->id : null,
            'paid_by' => $request->input('status') === Benefit::STATUS_PAID ? $request->user()->id : null,
            'approved_date' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? now()->toDateString() : null,
            'payment_date' => $request->input('status') === Benefit::STATUS_PAID ? ($request->input('payment_date') ?: now()->toDateString()) : $request->input('payment_date'),
        ]);
        $audit->log('benefit_created', $benefit, [], $benefit->toArray());

        return redirect()->route('admin.benefits.index')->with('success', 'Benefit recorded.');
    }

    public function edit(Benefit $benefit): View
    {
        $this->authorize('update', $benefit);

        return view('admin.benefits.form', [
            'benefit' => $benefit,
            'staff' => Staff::query()->active()->orderBy('full_name')->get(),
            'benefitTypes' => BenefitType::query()->orderBy('name')->get(),
        ]);
    }

    public function update(StoreBenefitRequest $request, Benefit $benefit, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $benefit);
        $old = $benefit->toArray();
        $benefit->update([
            ...$request->validated(),
            'approved_by' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? ($benefit->approved_by ?: $request->user()->id) : $benefit->approved_by,
            'paid_by' => $request->input('status') === Benefit::STATUS_PAID ? ($benefit->paid_by ?: $request->user()->id) : $benefit->paid_by,
            'approved_date' => in_array($request->input('status'), [Benefit::STATUS_APPROVED, Benefit::STATUS_PAID], true) ? ($benefit->approved_date ?: now()->toDateString()) : $benefit->approved_date,
            'payment_date' => $request->input('status') === Benefit::STATUS_PAID ? ($request->input('payment_date') ?: now()->toDateString()) : $request->input('payment_date'),
        ]);
        $audit->log('benefit_updated', $benefit, $old, $benefit->fresh()->toArray());

        return redirect()->route('admin.benefits.index')->with('success', 'Benefit updated.');
    }

    public function markPaid(Request $request, Benefit $benefit, AuditService $audit): RedirectResponse
    {
        $this->authorize('update', $benefit);
        $request->validate(['payment_date' => ['required', 'date']]);

        $old = $benefit->toArray();
        $benefit->update([
            'status' => Benefit::STATUS_PAID,
            'payment_date' => $request->input('payment_date'),
            'paid_by' => $request->user()->id,
            'approved_by' => $benefit->approved_by ?: $request->user()->id,
            'approved_date' => $benefit->approved_date ?: now()->toDateString(),
        ]);
        $audit->log('benefit_marked_paid', $benefit, $old, $benefit->fresh()->toArray());

        return back()->with('success', 'Benefit marked as paid.');
    }
}

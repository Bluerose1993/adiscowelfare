<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBenefitTypeRequest;
use App\Models\BenefitType;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BenefitTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.benefit-types.index', [
            'benefitTypes' => BenefitType::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.benefit-types.form', ['benefitType' => new BenefitType()]);
    }

    public function store(StoreBenefitTypeRequest $request, AuditService $audit): RedirectResponse
    {
        $benefitType = BenefitType::query()->create([
            ...$request->validated(),
            'requires_approval' => $request->boolean('requires_approval', true),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $audit->log('benefit_type_created', $benefitType, [], $benefitType->toArray());

        return redirect()->route('admin.benefit-types.index')->with('success', 'Benefit type saved.');
    }

    public function edit(BenefitType $benefitType): View
    {
        return view('admin.benefit-types.form', compact('benefitType'));
    }

    public function update(StoreBenefitTypeRequest $request, BenefitType $benefitType, AuditService $audit): RedirectResponse
    {
        $old = $benefitType->toArray();
        $benefitType->update([
            ...$request->validated(),
            'requires_approval' => $request->boolean('requires_approval'),
            'is_active' => $request->boolean('is_active'),
        ]);
        $audit->log('benefit_type_updated', $benefitType, $old, $benefitType->fresh()->toArray());

        return redirect()->route('admin.benefit-types.index')->with('success', 'Benefit type updated.');
    }
}

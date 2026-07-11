<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewBenefitRequestRequest;
use App\Http\Requests\SubmitBenefitRequestRequest;
use App\Models\BenefitRequest;
use App\Models\BenefitRequestAttachment;
use App\Models\BenefitType;
use App\Services\AuditService;
use App\Services\BenefitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BenefitRequestController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $query = BenefitRequest::query()->with(['staff', 'benefitType', 'resultingBenefit'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.benefit-requests.index', [
            'requests' => $query->paginate(50)->withQueryString(),
            'status' => $request->input('status'),
        ]);
    }

    public function adminShow(BenefitRequest $benefitRequest): View
    {
        $this->authorize('review', $benefitRequest);

        return view('admin.benefit-requests.show', [
            'requestRecord' => $benefitRequest->load(['staff', 'benefitType', 'attachments', 'resultingBenefit']),
        ]);
    }

    public function review(ReviewBenefitRequestRequest $request, BenefitRequest $benefitRequest, BenefitService $benefits, AuditService $audit): RedirectResponse
    {
        $this->authorize('review', $benefitRequest);
        $old = $benefitRequest->toArray();

        if ($request->input('status') === BenefitRequest::STATUS_APPROVED) {
            $benefit = $benefits->approveRequest(
                $benefitRequest,
                (float) $request->input('approved_amount'),
                $request->input('review_notes'),
                $request->user()->id
            );
            $audit->log('benefit_request_approved', $benefitRequest, $old, $benefitRequest->fresh()->toArray());
            $audit->log('benefit_created_from_request', $benefit, [], $benefit->toArray());

            return redirect()->route('admin.benefit-requests.show', $benefitRequest)->with('success', 'Request approved and pending benefit created.');
        }

        $benefitRequest->update([
            'status' => $request->input('status'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);
        $audit->log('benefit_request_'.$request->input('status'), $benefitRequest, $old, $benefitRequest->fresh()->toArray());

        return back()->with('success', 'Benefit request updated.');
    }

    public function staffIndex(Request $request): View
    {
        $staff = $request->user()->staff;

        return view('staff.requests.index', [
            'requests' => BenefitRequest::query()->with('benefitType')->where('staff_id', $staff->id)->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BenefitRequest::class);

        return view('staff.requests.form', [
            'benefitTypes' => BenefitType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(SubmitBenefitRequestRequest $request, AuditService $audit): RedirectResponse
    {
        $staff = $request->user()->staff;
        $benefitType = BenefitType::query()->where('is_active', true)->findOrFail($request->integer('benefit_type_id'));

        $benefitRequest = DB::transaction(function () use ($request, $staff, $benefitType, $audit) {
            $benefitRequest = BenefitRequest::query()->create([
                ...$request->safe()->except(['attachment', 'requested_amount']),
                'requested_amount' => $benefitType->default_amount,
                'staff_id' => $staff->id,
                'status' => BenefitRequest::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $stored = $file->store('benefit-request-attachments', 'public');
                BenefitRequestAttachment::query()->create([
                    'benefit_request_id' => $benefitRequest->id,
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_filename' => Str::afterLast($stored, '/'),
                    'path' => $stored,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }

            $audit->log('benefit_request_submitted', $benefitRequest, [], $benefitRequest->toArray());

            return $benefitRequest;
        });

        return redirect()->route('staff.requests.show', $benefitRequest)->with('success', 'Benefit request submitted.');
    }

    public function staffShow(Request $request, BenefitRequest $benefitRequest): View
    {
        $this->authorize('view', $benefitRequest);

        return view('staff.requests.show', [
            'requestRecord' => $benefitRequest->load(['benefitType', 'attachments', 'resultingBenefit']),
        ]);
    }
}

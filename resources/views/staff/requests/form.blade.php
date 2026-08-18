@php($isAdjustment = isset($requestRecord) && $requestRecord)
@extends('layouts.app', ['title' => $isAdjustment ? 'Adjust Benefit Request' : 'Submit Benefit Request'])

@section('content')
<form method="post" action="{{ $isAdjustment ? route('staff.requests.update', $requestRecord) : route('staff.requests.store') }}" enctype="multipart/form-data" data-prevent-double-submit="true">
    @csrf
    @if($isAdjustment) @method('PUT') @endif
    @if($isAdjustment)<div class="alert alert-warning"><strong>Admin instructions:</strong> {{ $requestRecord->review_notes }}</div>@endif
    <div class="card"><div class="card-body"><div class="row">
        <div class="col-md-6 form-group"><label class="required">Benefit Type</label><select id="benefitTypeSelect" name="benefit_type_id" class="form-control" required>@foreach($benefitTypes as $type)<option value="{{ $type->id }}" data-default-amount="{{ $type->default_amount }}" @selected(old('benefit_type_id', $requestRecord?->benefit_type_id) == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
        <div class="col-md-6 form-group"><label class="required">Subject</label><input name="subject" class="form-control" required value="{{ old('subject', $requestRecord?->subject) }}"></div>
        <div class="col-md-4 form-group"><label>Incident Date</label><input name="incident_date" type="date" class="form-control" value="{{ old('incident_date', $requestRecord?->incident_date?->format('Y-m-d')) }}"></div>
        <div class="col-md-4 form-group"><label>Requested Amount</label><input id="requestedAmount" name="requested_amount" type="number" step="0.01" class="form-control profile-locked" readonly><small id="amountHelp" class="text-muted"><i class="fas fa-lock"></i> Set automatically from the selected benefit type.</small></div>
        <div class="col-md-4 form-group"><label class="required">Upload Proof</label><input name="attachment" type="file" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @required(!$isAdjustment || $requestRecord->attachments->isEmpty())><small class="text-muted">{{ $isAdjustment && $requestRecord->attachments->isNotEmpty() ? 'Leave blank to keep the existing proof, or select a replacement.' : 'Proof is compulsory.' }} PDF, image or Word file; maximum 5 MB.</small></div>
        <div class="col-12 form-group"><label class="required">Description</label><textarea name="description" class="form-control" rows="5" required>{{ old('description', $requestRecord?->description) }}</textarea></div>
    </div></div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-paper-plane"></i> {{ $isAdjustment ? 'Resubmit Adjustments' : 'Submit Request' }}</button></div></div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const type = document.getElementById('benefitTypeSelect');
    const amount = document.getElementById('requestedAmount');
    const help = document.getElementById('amountHelp');
    function applyDefaultAmount() {
        const configured = type.options[type.selectedIndex]?.dataset.defaultAmount;
        amount.value = configured === undefined || configured === '' ? '' : Number(configured).toFixed(2);
        help.innerHTML = configured === undefined || configured === ''
            ? '<i class="fas fa-info-circle"></i> No default amount is configured for this benefit type.'
            : '<i class="fas fa-lock"></i> This amount is fixed by the selected benefit type.';
    }
    type.addEventListener('change', applyDefaultAmount);
    applyDefaultAmount();
});
</script>
@endpush

@extends('layouts.app', ['title' => 'Benefit Request'])

@section('content')
<div class="mb-3"><a class="btn btn-outline-primary" href="{{ route('staff.requests.print', $requestRecord) }}" target="_blank" rel="noopener"><i class="fas fa-print"></i> Print / Save PDF</a></div>
@if($requestRecord->status === \App\Models\BenefitRequest::STATUS_RETURNED)<div class="alert alert-warning"><strong>Adjustment required:</strong> {{ $requestRecord->review_notes }} <a href="{{ route('staff.requests.edit', $requestRecord) }}" class="btn btn-sm btn-warning ml-2"><i class="fas fa-edit"></i> Edit and Resubmit</a></div>@endif
<div class="card"><div class="card-body">
    <h4>{{ $requestRecord->subject }}</h4>
    <p>{{ $requestRecord->description }}</p>
    <dl class="row">
        <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $requestRecord->benefitType?->name }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge badge-info">{{ str_replace('_', ' ', $requestRecord->status) }}</span></dd>
        <dt class="col-sm-3">Requested Amount</dt><dd class="col-sm-9">{{ $requestRecord->requested_amount ? number_format($requestRecord->requested_amount, 2) : '-' }}</dd>
        @if($requestRecord->approved_amount)<dt class="col-sm-3">Approved Amount</dt><dd class="col-sm-9"><strong>{{ number_format($requestRecord->approved_amount, 2) }}</strong></dd>@endif
        @if($requestRecord->approved_at)<dt class="col-sm-3">Approved At</dt><dd class="col-sm-9">{{ $requestRecord->approved_at->format('d M Y, H:i:s') }}</dd>@endif
        @if($requestRecord->receipt_confirmed_at)
            <dt class="col-sm-3">Amount Received</dt><dd class="col-sm-9"><strong>GHS {{ number_format($requestRecord->received_amount, 2) }}</strong></dd>
            <dt class="col-sm-3">Confirmed At</dt><dd class="col-sm-9">{{ $requestRecord->receipt_confirmed_at->format('d M Y, H:i:s') }}</dd>
        @endif
        <dt class="col-sm-3">Review Notes</dt><dd class="col-sm-9">{{ $requestRecord->review_notes ?: '-' }}</dd>
    </dl>
</div></div>
@if(in_array($requestRecord->status, [\App\Models\BenefitRequest::STATUS_APPROVED, \App\Models\BenefitRequest::STATUS_PAID], true) && !$requestRecord->receipt_confirmed_at)
<div class="card card-success"><div class="card-header"><h3 class="card-title">Confirm Benefit Received</h3></div>
    <form method="post" action="{{ route('staff.requests.confirm-receipt', $requestRecord) }}" class="card-body" data-prevent-double-submit="true">@csrf
        <p>Enter the amount you actually received. Your confirmation and its date and time will be kept with this request.</p>
        <div class="form-group"><label for="receivedAmount">Amount received (GHS)</label><input id="receivedAmount" name="received_amount" type="number" min="0.01" max="99999999.99" step="0.01" value="{{ old('received_amount', $requestRecord->approved_amount) }}" class="form-control" required></div>
        <div class="form-check mb-3"><input id="confirmReceipt" name="confirm_receipt" value="1" type="checkbox" class="form-check-input" required><label for="confirmReceipt" class="form-check-label">I confirm that I received the amount entered above.</label></div>
        <button class="btn btn-success"><i class="fas fa-check-circle"></i> Confirm Receipt</button>
    </form>
</div>
@endif
@endsection

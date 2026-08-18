@extends('layouts.app', ['title' => 'Benefit Request'])

@section('content')
@if($requestRecord->status === \App\Models\BenefitRequest::STATUS_RETURNED)<div class="alert alert-warning"><strong>Adjustment required:</strong> {{ $requestRecord->review_notes }} <a href="{{ route('staff.requests.edit', $requestRecord) }}" class="btn btn-sm btn-warning ml-2"><i class="fas fa-edit"></i> Edit and Resubmit</a></div>@endif
<div class="card"><div class="card-body">
    <h4>{{ $requestRecord->subject }}</h4>
    <p>{{ $requestRecord->description }}</p>
    <dl class="row">
        <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $requestRecord->benefitType?->name }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge badge-info">{{ str_replace('_', ' ', $requestRecord->status) }}</span></dd>
        <dt class="col-sm-3">Requested Amount</dt><dd class="col-sm-9">{{ $requestRecord->requested_amount ? number_format($requestRecord->requested_amount, 2) : '-' }}</dd>
        @if($requestRecord->approved_amount)<dt class="col-sm-3">Approved Amount</dt><dd class="col-sm-9"><strong>{{ number_format($requestRecord->approved_amount, 2) }}</strong></dd>@endif
        <dt class="col-sm-3">Review Notes</dt><dd class="col-sm-9">{{ $requestRecord->review_notes ?: '-' }}</dd>
    </dl>
</div></div>
@endsection

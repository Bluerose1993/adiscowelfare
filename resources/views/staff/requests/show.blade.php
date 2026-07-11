@extends('layouts.app', ['title' => 'Benefit Request'])

@section('content')
<div class="card"><div class="card-body">
    <h4>{{ $requestRecord->subject }}</h4>
    <p>{{ $requestRecord->description }}</p>
    <dl class="row">
        <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $requestRecord->benefitType?->name }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge badge-info">{{ str_replace('_', ' ', $requestRecord->status) }}</span></dd>
        <dt class="col-sm-3">Requested Amount</dt><dd class="col-sm-9">{{ $requestRecord->requested_amount ? number_format($requestRecord->requested_amount, 2) : '-' }}</dd>
        <dt class="col-sm-3">Review Notes</dt><dd class="col-sm-9">{{ $requestRecord->review_notes ?: '-' }}</dd>
    </dl>
</div></div>
@endsection

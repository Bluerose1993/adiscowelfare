@extends('layouts.app', ['title' => 'Benefit Request'])

@section('content')
<div class="row">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h4>{{ $requestRecord->subject }}</h4>
            <p>{{ $requestRecord->description }}</p>
            <dl class="row">
                <dt class="col-sm-4">Staff</dt><dd class="col-sm-8">{{ $requestRecord->staff?->full_name }}</dd>
                <dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ $requestRecord->benefitType?->name }}</dd>
                <dt class="col-sm-4">Requested Amount</dt><dd class="col-sm-8">{{ $requestRecord->requested_amount ? number_format($requestRecord->requested_amount, 2) : '-' }}</dd>
                <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge badge-info">{{ str_replace('_', ' ', $requestRecord->status) }}</span></dd>
                <dt class="col-sm-4">Resulting Benefit</dt><dd class="col-sm-8">{{ $requestRecord->resultingBenefit ? $requestRecord->resultingBenefit->title : '-' }}</dd>
            </dl>
        </div></div>
    </div>
    <div class="col-lg-5">
        <form method="post" action="{{ route('admin.benefit-requests.review', $requestRecord) }}">
            @csrf
            <div class="card card-primary"><div class="card-header"><h3 class="card-title">Review</h3></div><div class="card-body">
                <div class="form-group"><label>Status</label><select name="status" class="form-control">@foreach(['under_review','approved','rejected','cancelled','paid'] as $item)<option value="{{ $item }}">{{ str_replace('_', ' ', ucfirst($item)) }}</option>@endforeach</select></div>
                <div class="form-group"><label>Approved Amount</label><input name="approved_amount" type="number" step="0.01" class="form-control" value="{{ old('approved_amount', $requestRecord->requested_amount) }}"></div>
                <div class="form-group"><label>Review Notes</label><textarea name="review_notes" class="form-control" rows="3">{{ old('review_notes') }}</textarea></div>
            </div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-check"></i> Save Review</button></div></div>
        </form>
    </div>
</div>
@endsection

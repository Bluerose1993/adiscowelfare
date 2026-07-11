@extends('layouts.app', ['title' => $benefit->exists ? 'Edit Benefit' : 'Record Benefit'])

@section('content')
<form method="post" action="{{ $benefit->exists ? route('admin.benefits.update', $benefit) : route('admin.benefits.store') }}" data-prevent-double-submit="true">
    @csrf
    @if($benefit->exists) @method('put') @endif
    <div class="card"><div class="card-body"><div class="row">
        <div class="col-md-6 form-group"><label class="required">Staff</label><select name="staff_id" class="form-control" required>@foreach($staff as $member)<option value="{{ $member->id }}" @selected(old('staff_id', $benefit->staff_id) == $member->id)>{{ $member->full_name }} {{ $member->staff_id ? '('.$member->staff_id.')' : '' }}</option>@endforeach</select></div>
        <div class="col-md-6 form-group"><label class="required">Benefit Type</label><select name="benefit_type_id" class="form-control" required>@foreach($benefitTypes as $type)<option value="{{ $type->id }}" @selected(old('benefit_type_id', $benefit->benefit_type_id) == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
        <div class="col-md-6 form-group"><label class="required">Title</label><input name="title" class="form-control" required value="{{ old('title', $benefit->title) }}"></div>
        <div class="col-md-3 form-group"><label class="required">Amount</label><input name="amount" type="number" step="0.01" min="0.01" class="form-control" required value="{{ old('amount', $benefit->amount) }}"></div>
        <div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control">@foreach(['pending','approved','paid','rejected','cancelled'] as $item)<option value="{{ $item }}" @selected(old('status', $benefit->status ?: 'pending') === $item)>{{ ucfirst($item) }}</option>@endforeach</select></div>
        <div class="col-md-4 form-group"><label>Incident Date</label><input name="incident_date" type="date" class="form-control" value="{{ old('incident_date', optional($benefit->incident_date)->format('Y-m-d')) }}"></div>
        <div class="col-md-4 form-group"><label>Payment Date</label><input name="payment_date" type="date" class="form-control" value="{{ old('payment_date', optional($benefit->payment_date)->format('Y-m-d')) }}"></div>
        <div class="col-12 form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $benefit->description) }}</textarea></div>
        <div class="col-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $benefit->notes) }}</textarea></div>
    </div></div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Benefit</button></div></div>
</form>
@endsection

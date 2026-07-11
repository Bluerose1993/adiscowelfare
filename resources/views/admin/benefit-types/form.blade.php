@extends('layouts.app', ['title' => $benefitType->exists ? 'Edit Benefit Type' : 'Add Benefit Type'])

@section('content')
<form method="post" action="{{ $benefitType->exists ? route('admin.benefit-types.update', $benefitType) : route('admin.benefit-types.store') }}">
    @csrf
    @if($benefitType->exists) @method('put') @endif
    <div class="card"><div class="card-body">
        <div class="row">
            <div class="col-md-4 form-group"><label class="required">Name</label><input name="name" class="form-control" required value="{{ old('name', $benefitType->name) }}"></div>
            <div class="col-md-4 form-group"><label>Default Amount</label><input name="default_amount" type="number" step="0.01" min="0" class="form-control" value="{{ old('default_amount', $benefitType->default_amount) }}"></div>
            <div class="col-md-2 form-group d-flex align-items-center"><div class="custom-control custom-checkbox mt-4"><input type="checkbox" class="custom-control-input" id="requires_approval" name="requires_approval" value="1" @checked(old('requires_approval', $benefitType->exists ? $benefitType->requires_approval : true))><label class="custom-control-label" for="requires_approval">Approval</label></div></div>
            <div class="col-md-2 form-group d-flex align-items-center"><div class="custom-control custom-checkbox mt-4"><input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $benefitType->exists ? $benefitType->is_active : true))><label class="custom-control-label" for="is_active">Active</label></div></div>
            <div class="col-12 form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $benefitType->description) }}</textarea></div>
        </div>
    </div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save</button></div></div>
</form>
@endsection

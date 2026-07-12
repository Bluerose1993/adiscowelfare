@extends('layouts.app', ['title' => 'My Profile'])

@section('content')
@if(auth()->user()->hasRole('Administrator'))<div class="mb-3"><a href="{{ route('admin.password.edit') }}" class="btn btn-outline-primary"><i class="fas fa-key"></i> Change Password</a></div>@endif
<form method="post" action="{{ $profileUpdateRoute ?? route('staff.profile.update') }}" data-prevent-double-submit="true">@csrf @method('PUT')
<div class="card"><div class="card-header"><h3 class="card-title">Personal &amp; Employment Information</h3></div><div class="card-body"><div class="row">
    <div class="col-md-6 form-group"><label class="required">Full Name</label><input name="full_name" class="form-control" value="{{ old('full_name', $staff->full_name) }}" required></div>
    <div class="col-md-3 form-group"><label>Staff ID</label><input class="form-control profile-locked" value="{{ $staff->staff_id ?: 'Unverified' }}" readonly><small class="text-muted"><i class="fas fa-lock"></i> Only an administrator can change Staff ID.</small></div>
    <div class="col-md-3 form-group"><label>Gender</label><input name="gender" class="form-control" value="{{ old('gender', $staff->gender) }}"></div>
    <div class="col-md-6 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}"></div>
    <div class="col-md-6 form-group"><label>Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $staff->email) }}"></div>
    <div class="col-md-6 form-group"><label>Department</label><input name="department" class="form-control" value="{{ old('department', $staff->department) }}"></div>
    <div class="col-md-6 form-group"><label>Position</label><input name="position" class="form-control" value="{{ old('position', $staff->position) }}"></div>
    <div class="col-md-4 form-group"><label>Employment Status</label><input name="employment_status" class="form-control" value="{{ old('employment_status', $staff->employment_status) }}"></div>
    <div class="col-md-4 form-group"><label>Date Joined</label><input name="date_joined" type="date" class="form-control" value="{{ old('date_joined', $staff->date_joined?->format('Y-m-d')) }}"></div>
    <div class="col-md-4 form-group"><label>Association Joined</label><input name="association_joined_at" type="date" class="form-control" value="{{ old('association_joined_at', $staff->association_joined_at?->format('Y-m-d')) }}"></div>
    <div class="col-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $staff->notes) }}</textarea></div>
</div></div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save My Profile</button></div></div>
</form>
@endsection

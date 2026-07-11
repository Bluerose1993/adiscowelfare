@extends('layouts.app', ['title' => $staff->exists ? 'Edit Staff' : 'Add Staff'])

@section('content')
<form method="post" action="{{ $staff->exists ? route('admin.staff.update', $staff) : route('admin.staff.store') }}" data-prevent-double-submit="true">
    @csrf
    @if($staff->exists) @method('put') @endif
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group"><label>Staff ID</label><input name="staff_id" class="form-control" value="{{ old('staff_id', $staff->staff_id) }}"></div>
                <div class="col-md-8 form-group"><label class="required">Full Name</label><input name="full_name" class="form-control" required value="{{ old('full_name', $staff->full_name) }}"></div>
                <div class="col-md-4 form-group"><label>Phone</label><input name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}"></div>
                <div class="col-md-4 form-group"><label>Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $staff->email) }}"></div>
                <div class="col-md-4 form-group"><label>Gender</label><input name="gender" class="form-control" value="{{ old('gender', $staff->gender) }}"></div>
                <div class="col-md-4 form-group"><label>Department</label><input name="department" class="form-control" value="{{ old('department', $staff->department) }}"></div>
                <div class="col-md-4 form-group"><label>Position</label><input name="position" class="form-control" value="{{ old('position', $staff->position) }}"></div>
                <div class="col-md-4 form-group"><label>Employment Status</label><input name="employment_status" class="form-control" value="{{ old('employment_status', $staff->employment_status) }}"></div>
                <div class="col-md-4 form-group"><label>Date Joined</label><input name="date_joined" type="date" class="form-control" value="{{ old('date_joined', optional($staff->date_joined)->format('Y-m-d')) }}"></div>
                <div class="col-md-4 form-group"><label>Association Joined</label><input name="association_joined_at" type="date" class="form-control" value="{{ old('association_joined_at', optional($staff->association_joined_at)->format('Y-m-d')) }}"></div>
                <div class="col-md-4 form-group d-flex align-items-center"><div class="custom-control custom-switch mt-4"><input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @checked(old('is_active', $staff->exists ? $staff->is_active : true))><label class="custom-control-label" for="is_active">Active</label></div></div>
                <div class="col-md-12 form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $staff->notes) }}</textarea></div>
                @unless($staff->exists)
                    <div class="col-md-4 form-group"><div class="custom-control custom-checkbox mt-2"><input type="checkbox" class="custom-control-input" id="create_user" name="create_user" value="1"><label class="custom-control-label" for="create_user">Create login account</label></div></div>
                    <div class="col-md-4 form-group"><label>Temporary Password</label><input name="temporary_password" class="form-control" value="ChangeMe123!"></div>
                @endunless
            </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Staff</button></div>
    </div>
</form>
@endsection

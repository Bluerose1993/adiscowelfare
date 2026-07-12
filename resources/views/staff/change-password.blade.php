@extends('layouts.app', ['title' => 'Change Password'])

@section('content')
@if(auth()->user()->must_change_password)
    <div class="alert alert-warning"><strong>Password change required.</strong> Your current password is the phone number supplied by the administrator. Choose a private password before continuing.</div>
@endif
<form method="post" action="{{ $passwordUpdateRoute ?? route('staff.password.update') }}" data-prevent-double-submit="true">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="form-group">
                <label class="required">Current Password</label>
                <input name="current_password" type="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="required">New Password</label>
                <input name="password" type="password" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label class="required">Confirm New Password</label>
                <input name="password_confirmation" type="password" class="form-control" required minlength="8">
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary"><i class="fas fa-save"></i> Change Password</button>
        </div>
    </div>
</form>
@endsection

@extends('layouts.app', ['title' => $administrator->exists ? 'Edit Administrator' : 'Add Administrator'])

@section('content')
<form method="post" action="{{ $administrator->exists ? route('admin.administrators.update', $administrator) : route('admin.administrators.store') }}" data-prevent-double-submit="true">
    @csrf @if($administrator->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-5"><div class="card"><div class="card-header"><h3 class="card-title">Account Details</h3></div><div class="card-body">
        <div class="form-group"><label class="required">Full name</label><input name="name" class="form-control" value="{{ old('name', $administrator->name) }}" required></div>
        <div class="form-group"><label class="required">Username</label><input name="username" class="form-control" value="{{ old('username', $administrator->username) }}" required></div>
        <div class="form-group"><label>Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $administrator->email) }}"></div>
        <div class="form-group"><label class="required">Status</label><select name="status" class="form-control"><option value="active" @selected(old('status', $administrator->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $administrator->status) === 'inactive')>Inactive</option></select></div>
        <div class="form-group"><label>{{ $administrator->exists ? 'New password (optional)' : 'Password' }}</label><input name="password" type="password" class="form-control" {{ $administrator->exists ? '' : 'required' }} minlength="8"></div>
        <div class="form-group"><label>Confirm password</label><input name="password_confirmation" type="password" class="form-control" {{ $administrator->exists ? '' : 'required' }} minlength="8"></div>
    </div></div></div>
    <div class="col-lg-7"><div class="card"><div class="card-header"><h3 class="card-title">System Options &amp; Access</h3></div><div class="card-body"><p class="text-muted">Checked options appear in this administrator's navigation and are enforced on the server.</p><div class="permission-grid">
        @foreach($permissions as $permission)<label class="permission-option"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', $selectedPermissions), true))><span><i class="fas fa-check"></i></span><strong>{{ ucwords($permission->name) }}</strong></label>@endforeach
    </div></div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Administrator &amp; Access</button></div></div></div></div>
</form>
@if($administrator->exists)
<div class="card"><div class="card-header"><h3 class="card-title">Reset Password</h3></div><form method="post" action="{{ route('admin.administrators.reset-password', $administrator) }}" class="card-body">@csrf<div class="form-row align-items-end"><div class="col-md-4"><label>New password</label><input name="password" type="password" minlength="8" class="form-control" required></div><div class="col-md-4"><label>Confirm password</label><input name="password_confirmation" type="password" minlength="8" class="form-control" required></div><div class="col-md-4"><button class="btn btn-warning"><i class="fas fa-key"></i> Reset Password</button></div></div></form></div>
@endif
@endsection

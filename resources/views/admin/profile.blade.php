@extends('layouts.app', ['title' => 'My Profile'])

@section('content')
<div class="mb-3"><a href="{{ route('admin.password.edit') }}" class="btn btn-outline-primary"><i class="fas fa-key"></i> Change Password</a></div>
<form method="post" action="{{ route('admin.profile.update') }}" data-prevent-double-submit="true">@csrf @method('PUT')
<div class="card"><div class="card-header"><h3 class="card-title">Administrator Profile</h3></div><div class="card-body"><div class="row">
    <div class="col-md-6 form-group"><label class="required">Full Name</label><input name="full_name" class="form-control" value="{{ old('full_name', $administrator->name) }}" required></div>
    <div class="col-md-6 form-group"><label>Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $administrator->email) }}"></div>
    <div class="col-md-6 form-group"><label>Username</label><input class="form-control profile-locked" value="{{ $administrator->username }}" readonly><small class="text-muted"><i class="fas fa-lock"></i> Username is managed through Administrator Access.</small></div>
</div></div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save My Profile</button></div></div>
</form>
@endsection

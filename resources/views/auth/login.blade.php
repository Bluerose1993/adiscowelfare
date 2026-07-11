@extends('layouts.app', ['title' => 'Login'])

@section('content')
@php
    $applicationName = \App\Models\Setting::value('application_name', config('app.name'));
    $logoPath = \App\Models\Setting::value('logo_path');
@endphp
<div class="login-page">
    <div class="login-box">
        <div class="card card-outline card-primary">
            <div class="card-header text-center">
                @if($logoPath)<img class="login-logo-image" src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ $applicationName }} logo">@else<span class="login-logo-fallback"><i class="fas fa-hands-helping"></i></span>@endif
                <strong class="login-application-name">{{ $applicationName }}</strong>
                <small>{{ \App\Models\Setting::value('institution_name', '') }}</small>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Sign in with your username or email</p>
                <form action="{{ route('login.store') }}" method="post" data-prevent-double-submit="true">
                    @csrf
                    <div class="input-group mb-3">
                        <input name="login" value="{{ old('login') }}" class="form-control" placeholder="Username or email" required autofocus>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                    </div>
                    <div class="input-group mb-3">
                        <input name="password" type="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

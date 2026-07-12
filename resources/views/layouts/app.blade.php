<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $applicationName = \App\Models\Setting::value('application_name', config('app.name'));
        $logoPath = \App\Models\Setting::value('logo_path');
        $faviconPath = \App\Models\Setting::value('favicon_path');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="session-timeout-seconds" content="{{ max((int) \App\Models\Setting::value('session_timeout_minutes', 120), 1) * 60 }}">
    <meta name="session-keep-alive-url" content="{{ route('session.keep-alive') }}">
    @endauth
    <title>{{ isset($title) ? $title.' | '.$applicationName : $applicationName }}</title>
    @if($faviconPath)<link rel="icon" href="{{ Storage::disk('public')->url($faviconPath) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="hold-transition sidebar-mini layout-fixed welfare-shell">
@auth
@if(\App\Models\Setting::value('system_mode', 'production') === 'debug')
<div class="debug-mode-banner"><i class="fas fa-flask"></i><strong>DEBUG MODE</strong><span>Deletion approvals are bypassed. Demo data can be removed immediately.</span></div>
@endif
@endauth
<div class="wrapper">
    @auth
        @php
            $homeRoute = auth()->user()->hasRole('Administrator')
                ? (auth()->user()->can('view dashboard') ? route('admin.dashboard')
                    : (auth()->user()->can('manage staff') ? route('admin.staff.index')
                    : (auth()->user()->can('manage dues') ? route('admin.dues.record')
                    : (auth()->user()->can('manage benefits') ? route('admin.benefits.index')
                    : (auth()->user()->can('review benefit requests') ? route('admin.benefit-requests.index')
                    : (auth()->user()->can('view reports') ? route('admin.reports.dues')
                    : (auth()->user()->can('manage administrators') ? route('admin.administrators.index')
                    : (auth()->user()->can('manage settings') ? route('admin.settings.index')
                    : route('admin.audit.index')))))))))
                : route('staff.dashboard');
        @endphp
        <nav class="main-header navbar navbar-expand navbar-white navbar-light welfare-topbar">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="{{ $homeRoute }}" class="nav-link">Home</a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link user-chip" href="{{ auth()->user()->hasRole('Administrator') ? route('admin.profile.edit') : route('staff.profile') }}"><span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span class="user-name">{{ auth()->user()->name }}</span></a>
                </li>
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="btn btn-link nav-link" type="submit"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </form>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4 welfare-sidebar">
            <a href="{{ $homeRoute }}" class="brand-link">
                <span class="brand-mark">@if($logoPath)<img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ $applicationName }} logo">@else<i class="fas fa-hands-helping"></i>@endif</span><span class="brand-text ml-2">{{ $applicationName }}</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        @if(auth()->user()->hasRole('Administrator'))
                            @can('view dashboard')<li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>@endcan
                            @can('manage staff')
                            <li class="nav-header">STAFF MANAGEMENT</li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.staff.index') }}"><i class="nav-icon fas fa-users"></i><p>All Staff</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.staff.create') }}"><i class="nav-icon fas fa-user-plus"></i><p>Add Staff</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.import.index') }}"><i class="nav-icon fas fa-file-import"></i><p>Import Staff</p></a></li>
                            @endcan
                            @can('manage dues')
                            <li class="nav-header">DUES MANAGEMENT</li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.dues.record') }}"><i class="nav-icon fas fa-cash-register"></i><p>Record Payment</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.dues.bulk') }}"><i class="nav-icon fas fa-list"></i><p>Bulk Entry</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.dues.index') }}"><i class="nav-icon fas fa-receipt"></i><p>Transactions</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.import.index') }}"><i class="nav-icon fas fa-file-import"></i><p>Import Dues</p></a></li>
                            @endcan
                            @if(auth()->user()->can('manage benefits') || auth()->user()->can('review benefit requests'))
                            <li class="nav-header">BENEFITS</li>
                            @endif
                            @can('manage benefits')
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.benefits.index') }}"><i class="nav-icon fas fa-hand-holding-heart"></i><p>All Benefits</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.benefits.index', ['status' => 'pending']) }}"><i class="nav-icon fas fa-clock"></i><p>Pending Benefits</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.benefit-types.index') }}"><i class="nav-icon fas fa-tags"></i><p>Benefit Types</p></a></li>
                            @endcan
                            @can('review benefit requests')
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.benefit-requests.index') }}"><i class="nav-icon fas fa-inbox"></i><p>Benefit Requests</p></a></li>
                            @endcan
                            @can('view reports')
                            <li class="nav-header">REPORTS</li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.dues') }}"><i class="nav-icon fas fa-table"></i><p>Dues Report</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.benefits') }}"><i class="nav-icon fas fa-chart-pie"></i><p>Benefits Report</p></a></li>
                            @endcan
                            <li class="nav-header">SYSTEM</li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.profile.edit') }}"><i class="nav-icon fas fa-user-circle"></i><p>My Profile</p></a></li>
                            @can('manage administrators')<li class="nav-item"><a class="nav-link" href="{{ route('admin.administrators.index') }}"><i class="nav-icon fas fa-user-shield"></i><p>Administrators</p></a></li>@endcan
                            @can('manage settings')
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.settings.index') }}"><i class="nav-icon fas fa-cogs"></i><p>Settings</p></a></li>
                            @endcan
                            @can('view audit logs')
                            <li class="nav-item"><a class="nav-link" href="{{ route('admin.audit.index') }}"><i class="nav-icon fas fa-history"></i><p>Audit Logs</p></a></li>
                            @endcan
                        @else
                            <li class="nav-item"><a class="nav-link" href="{{ route('staff.dashboard') }}"><i class="nav-icon fas fa-home"></i><p>Dashboard</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('staff.dues') }}"><i class="nav-icon fas fa-wallet"></i><p>My Dues</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('staff.benefits') }}"><i class="nav-icon fas fa-gift"></i><p>My Benefits</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('staff.requests.index') }}"><i class="nav-icon fas fa-file-alt"></i><p>My Requests</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('staff.requests.create') }}"><i class="nav-icon fas fa-plus-circle"></i><p>Submit Request</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('staff.profile') }}"><i class="nav-icon fas fa-user"></i><p>My Profile</p></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('staff.password.edit') }}"><i class="nav-icon fas fa-key"></i><p>Change Password</p></a></li>
                        @endif
                    </ul>
                </nav>
            </div>
        </aside>
    @endauth

    <div class="{{ auth()->check() ? 'content-wrapper' : '' }}">
        @auth
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-8"><span class="page-eyebrow">Welfare management</span><h1>{{ $title ?? 'Welfare Dues' }}</h1></div>
                        <div class="col-sm-4 text-sm-right">@yield('actions')</div>
                    </div>
                </div>
            </section>
        @endauth

        <section class="{{ auth()->check() ? 'content' : '' }}">
            <div class="{{ auth()->check() ? 'container-fluid' : '' }}">
                @yield('content')
            </div>
        </section>
    </div>
    @auth
        <footer class="main-footer">
            <strong>{{ \App\Models\Setting::value('association_name', 'Welfare Association') }}</strong>
            <span class="float-right d-none d-sm-inline">Secure dues &amp; benefits management</span>
        </footer>
    @endauth
</div>
@php($popupType = $errors->any() || session('error') ? 'error' : (session('success') ? 'success' : (session('status') || session('info') ? 'info' : null)))
@if($popupType)
<div class="modal fade" id="systemFeedbackModal" tabindex="-1" role="dialog" aria-labelledby="systemFeedbackTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content error-modal-content popup-{{ $popupType }}"><div class="modal-header"><div class="error-modal-icon"><i class="fas fa-{{ $popupType === 'success' ? 'check-circle' : ($popupType === 'info' ? 'info-circle' : 'exclamation-triangle') }}"></i></div><div><h5 class="modal-title" id="systemFeedbackTitle">{{ $popupType === 'success' ? 'Action completed' : ($popupType === 'info' ? 'System notification' : 'Unable to complete that action') }}</h5><small>{{ $popupType === 'error' ? 'Please review the message below and try again.' : 'Welfare portal notification' }}</small></div><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body">@foreach(['success','status','info','error'] as $key)@if(session($key))<p class="mb-1">{{ session($key) }}</p>@endif @endforeach @if($errors->any())<ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif</div><div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">Okay</button></div></div></div></div>
@endif
@auth
<div id="sessionTimeoutWarning" class="session-timeout-warning" role="alertdialog" aria-live="assertive" aria-labelledby="sessionTimeoutTitle" hidden>
    <div id="sessionCountdownClock" class="session-countdown-clock"><div><strong id="sessionCountdownValue">30</strong><span>seconds</span></div></div>
    <div><strong id="sessionTimeoutTitle">You will be logged out soon</strong><p>Click anywhere or press any key to keep your session active.</p></div>
</div>
@endauth
@stack('scripts')
@if($popupType)
<script>
(() => {
    const popup = document.getElementById('systemFeedbackModal');
    if (!popup) return;
    const close = () => {
        popup.classList.remove('show', 'system-popup-visible');
        popup.style.display = 'none';
        popup.setAttribute('aria-hidden', 'true');
    };
    popup.style.display = 'flex';
    popup.classList.add('show', 'system-popup-visible');
    popup.setAttribute('aria-hidden', 'false');
    popup.querySelectorAll('[data-dismiss="modal"]').forEach(button => button.addEventListener('click', close));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
})();
</script>
@endif
</body>
</html>

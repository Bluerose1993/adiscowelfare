@extends('layouts.app', ['title' => 'Settings'])

@section('content')
@php($systemMode = $settings['system_mode']->value ?? 'production')
<div class="card mode-card mode-{{ $systemMode }}"><div class="card-header"><h3 class="card-title"><i class="fas fa-{{ $systemMode === 'production' ? 'shield-alt' : 'flask' }}"></i> System Mode: {{ ucfirst($systemMode) }}</h3></div><div class="card-body"><div class="mode-switch-layout"><div><strong>{{ $systemMode === 'production' ? 'Production safeguards are active' : 'Debug deletion bypass is active' }}</strong><p class="mb-0">{{ $systemMode === 'production' ? 'Payment deletion requires a request and approval by a second administrator.' : 'Payment deletions happen immediately after the acting administrator confirms their password. Use only for removing test/demo data.' }}</p></div><form method="post" action="{{ route('admin.settings.mode') }}" class="mode-switch-form" data-prevent-double-submit="true">@csrf<input type="hidden" name="system_mode" value="{{ $systemMode === 'production' ? 'debug' : 'production' }}"><input name="password" type="password" class="form-control" placeholder="Your password" required><button class="btn btn-{{ $systemMode === 'production' ? 'warning' : 'success' }}"><i class="fas fa-exchange-alt"></i> Switch to {{ $systemMode === 'production' ? 'Debug' : 'Production' }} Mode</button></form></div></div></div>
<form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="system_mode" value="{{ $systemMode }}">
    <div class="card"><div class="card-body"><div class="row">
        <div class="col-md-6 form-group"><label>Association Name</label><input name="association_name" class="form-control" value="{{ old('association_name', $settings['association_name']->value ?? '') }}" required></div>
        <div class="col-md-6 form-group"><label>Institution Name</label><input name="institution_name" class="form-control" value="{{ old('institution_name', $settings['institution_name']->value ?? '') }}"></div>
        <div class="col-md-4 form-group"><label>Application Name</label><input name="application_name" class="form-control" value="{{ old('application_name', $settings['application_name']->value ?? '') }}" required></div>
        <div class="col-md-4 form-group"><label>Default Currency</label><input name="default_currency" class="form-control" value="{{ old('default_currency', $settings['default_currency']->value ?? 'GHS') }}" required></div>
        <div class="col-md-4 form-group"><label>Currency Symbol</label><input name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $settings['currency_symbol']->value ?? 'GHS') }}" required></div>
        <div class="col-md-4 form-group"><label>Financial Year Start Month</label><input name="financial_year_start_month" type="number" min="1" max="12" class="form-control" value="{{ old('financial_year_start_month', $settings['financial_year_start_month']->value ?? 1) }}" required></div>
        <div class="col-md-4 form-group"><label>Session Inactivity Timeout</label><div class="input-group"><input name="session_timeout_minutes" type="number" min="1" max="1440" class="form-control" value="{{ old('session_timeout_minutes', $settings['session_timeout_minutes']->value ?? 120) }}" required><div class="input-group-append"><span class="input-group-text">minutes</span></div></div><small class="text-muted">Applies to all users. A warning appears during the final 30 seconds.</small></div>
        <div class="col-md-6 form-group"><label>Application Logo</label><div class="branding-upload">@if($settings['logo_path']->value ?? null)<img src="{{ Storage::disk('public')->url($settings['logo_path']->value) }}" alt="Current logo">@endif<div><input name="logo" type="file" class="form-control-file" accept=".png,.jpg,.jpeg,.webp"><small class="text-muted">PNG, JPG or WebP. Maximum 2 MB.</small></div></div></div>
        <div class="col-md-6 form-group"><label>Browser Favicon</label><div class="branding-upload">@if($settings['favicon_path']->value ?? null)<img class="favicon-preview" src="{{ Storage::disk('public')->url($settings['favicon_path']->value) }}" alt="Current favicon">@endif<div><input name="favicon" type="file" class="form-control-file" accept=".png,.ico"><small class="text-muted">PNG or ICO. Maximum 512 KB.</small></div></div></div>
    </div></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">Add Dues Rate</h3></div><div class="card-body"><div class="row">
        <div class="col-md-4 form-group"><label>Name</label><input name="dues_name" class="form-control" value="Monthly dues"></div>
        <div class="col-md-4 form-group"><label>Amount</label><input name="dues_amount" type="number" step="0.01" min="0" class="form-control"></div>
        <div class="col-md-4 form-group"><label>Effective From</label><input name="dues_effective_from" type="date" class="form-control"></div>
    </div></div><div class="card-footer"><button class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button></div></div>
</form>
<div class="card"><div class="card-header"><h3 class="card-title">Dues Rates</h3></div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Name</th><th>Amount</th><th>From</th><th>To</th><th>Status</th></tr></thead><tbody>
@foreach($duesRates as $rate)<tr><td>{{ $rate->name }}</td><td>{{ number_format($rate->amount, 2) }}</td><td>{{ $rate->effective_from?->format('Y-m-d') }}</td><td>{{ $rate->effective_to?->format('Y-m-d') ?: '-' }}</td><td>{{ $rate->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach
</tbody></table></div></div>
@endsection

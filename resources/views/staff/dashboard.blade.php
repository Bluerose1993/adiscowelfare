@extends('layouts.app', ['title' => 'My Dashboard'])

@section('actions')
<form method="get" class="dashboard-year-filter"><label for="dashboardYear" class="mb-0">Dashboard year</label><select id="dashboardYear" name="year" class="form-control" onchange="this.form.submit()">@foreach($availableYears as $availableYear)<option value="{{ $availableYear }}" @selected($year == $availableYear)>{{ $availableYear }}</option>@endforeach</select><noscript><button class="btn btn-primary">Apply</button></noscript></form>
@endsection

@section('content')
<div class="card"><div class="card-body"><h4>Welcome, {{ $staff->full_name }}</h4><p class="mb-0">Staff ID: {{ $staff->staff_id ?: 'Unverified' }} <span class="year-context"><i class="fas fa-calendar-alt"></i> Showing {{ $year }}</span></p></div></div>
<div class="row">
    @foreach([
        ['Dues Paid '.$year, $summary['paid_year'], 'success'],
        ['Expected Dues '.$year, $summary['expected_year'], 'primary'],
        ['Outstanding '.$year, $summary['outstanding'], 'danger'],
        ['Credit Ahead '.$year, $summary['credit'], 'info'],
        ['Benefits Received '.$year, $summary['benefits_received'], 'secondary'],
        ['Pending Benefits '.$year, $summary['pending_benefits'], 'warning'],
    ] as [$label, $value, $color])
        <div class="col-lg-2 col-md-4"><div class="small-box bg-{{ $color }}"><div class="inner"><h4>{{ number_format($value, 2) }}</h4><p>{{ $label }}</p></div></div></div>
    @endforeach
</div>
<div class="card"><div class="card-header"><h3 class="card-title">{{ $year }} Dues Status</h3></div><div class="card-body"><div class="month-grid">
    @foreach($matrix as $row)
        @php($paid = in_array($row['status'], ['paid', 'overpaid'], true))
        <div class="month-tile month-state-{{ $paid ? 'paid' : 'unpaid' }}"><span class="month-check"><i class="fas fa-{{ $paid ? 'check' : 'exclamation' }}"></i></span><strong>{{ $row['month'] }}</strong><div>{{ number_format($row['paid'], 2) }} / {{ number_format($row['expected'], 2) }}</div><span class="month-status">{{ str_replace('_', ' ', $row['status']) }}</span></div>
    @endforeach
</div></div></div>
@endsection

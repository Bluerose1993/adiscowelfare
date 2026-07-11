@extends('layouts.app', ['title' => 'Administrator Dashboard'])

@section('actions')
<form class="form-inline justify-content-end" method="get">
    <input class="form-control form-control-sm mr-2" type="number" name="year" value="{{ $year }}" min="2000" max="2100">
    <button class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Apply</button>
</form>
@endsection

@section('content')
@php
    $cards = [
        ['Total Active Staff', $stats['active_staff'], 'bg-info', 'fas fa-users'],
        ['Completed First Login', $completedFirstLoginCount, 'bg-success', 'fas fa-user-check'],
        ['Dues This Month', number_format($stats['dues_this_month'], 2), 'bg-success', 'fas fa-calendar-check'],
        ['Dues This Year', number_format($stats['dues_this_year'], 2), 'bg-teal', 'fas fa-wallet'],
        ['Total Dues on Record', number_format($stats['dues_all_time'], 2), 'bg-primary', 'fas fa-coins'],
        ['Benefits Paid This Year', number_format($stats['benefits_paid_year'], 2), 'bg-warning', 'fas fa-hand-holding-heart'],
        ['Benefits Paid All Time', number_format($stats['benefits_paid_all_time'], 2), 'bg-secondary', 'fas fa-gift'],
        ['Pending Benefit Amount', number_format($stats['pending_benefit_amount'], 2), 'bg-danger', 'fas fa-clock'],
        ['Pending Requests', $stats['pending_requests'], 'bg-dark', 'fas fa-inbox'],
    ];
@endphp
<div class="row">
    @foreach($cards as [$label, $value, $class, $icon])
        <div class="col-lg-3 col-md-6">
            <div class="small-box {{ $class }}">
                <div class="inner"><h3>{{ $value }}</h3><p>{{ $label }}</p></div>
                <div class="icon"><i class="{{ $icon }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Monthly Dues Collected</h3></div>
            <div class="card-body"><canvas id="monthlyDuesChart" height="130"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Benefits by Type</h3></div>
            <div class="card-body"><canvas id="benefitChart" height="220"></canvas></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Recent Dues Payments</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Staff</th><th>Amount</th><th>Recorder</th></tr></thead>
                    <tbody>
                    @forelse($recentPayments as $payment)
                        <tr><td>{{ $payment->payment_date?->format('Y-m-d') }}</td><td>{{ $payment->staff?->full_name }}</td><td>{{ number_format($payment->amount, 2) }}</td><td>{{ $payment->recorder?->name }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No payments yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New Benefit Requests</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-sm">
                    <thead><tr><th>Staff</th><th>Type</th><th>Subject</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($newRequests as $request)
                        <tr><td>{{ $request->staff?->full_name }}</td><td>{{ $request->benefitType?->name }}</td><td><a href="{{ route('admin.benefit-requests.show', $request) }}">{{ $request->subject }}</a></td><td><span class="badge badge-info">{{ $request->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No new requests.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    new Chart(document.getElementById('monthlyDuesChart'), {
        type: 'bar',
        data: { labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], datasets: [{ label: 'Dues', data: @json($monthlyDues), backgroundColor: '#0f766e' }] },
        options: { responsive: true, maintainAspectRatio: false }
    });
    const benefitData = @json($benefitDistribution);
    new Chart(document.getElementById('benefitChart'), {
        type: 'doughnut',
        data: { labels: benefitData.map(item => item.name), datasets: [{ data: benefitData.map(item => item.total), backgroundColor: ['#0f766e','#2563eb','#f59e0b','#dc2626','#6b7280','#7c3aed'] }] },
        options: { responsive: true, maintainAspectRatio: false }
    });
});
</script>
@endpush

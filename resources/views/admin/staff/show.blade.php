@extends('layouts.app', ['title' => $staff->full_name])

@section('actions')
<a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
<a href="{{ route('admin.reports.statement', $staff) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-print"></i> Statement</a>
<button class="btn btn-sm btn-outline-danger" data-toggle="collapse" data-target="#staffDeletePanel"><i class="fas fa-trash"></i> Delete Staff</button>
@endsection

@section('content')
@php($debugMode = \App\Models\Setting::value('system_mode','production') === 'debug')
<div class="collapse" id="staffDeletePanel"><div class="card card-danger"><form method="post" action="{{ route('admin.staff.deletion-request',$staff) }}" class="card-body deletion-request-form">@csrf<div><strong>{{ $debugMode ? 'Delete staff immediately' : 'Request staff deletion' }}</strong><small class="d-block text-muted">{{ $debugMode ? 'Debug mode bypasses second-admin approval.' : 'A different admin must approve this request.' }}</small></div><input name="reason" class="form-control" placeholder="Reason" required><input name="password" type="password" class="form-control" placeholder="Your password" required><button class="btn btn-danger">{{ $debugMode ? 'Delete Staff' : 'Submit Request' }}</button></form></div></div>
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h5>{{ $staff->full_name }}</h5>
                <p class="mb-1"><strong>Staff ID:</strong> {{ $staff->staff_id ?: 'Unverified' }}</p>
                <p class="mb-1"><strong>Phone:</strong> {{ $staff->phone }}</p>
                <p class="mb-1"><strong>Department:</strong> {{ $staff->department }}</p>
                <p class="mb-1"><strong>Position:</strong> {{ $staff->position }}</p>
                <span class="badge badge-{{ $staff->is_active ? 'success' : 'secondary' }}">{{ $staff->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Login Account</h3></div>
            <div class="card-body">
                @if($staff->user)
                    <p>Username: <strong>{{ $staff->user->username }}</strong></p>
                    <form method="post" action="{{ route('admin.staff.reset-password', $staff) }}">
                        @csrf
                        <div class="input-group"><input name="temporary_password" class="form-control" value="ChangeMe123!" required><div class="input-group-append"><button class="btn btn-warning">Reset</button></div></div>
                    </form>
                @else
                    <form method="post" action="{{ route('admin.staff.create-account', $staff) }}">
                        @csrf
                        <div class="input-group"><input name="temporary_password" class="form-control" value="ChangeMe123!" required><div class="input-group-append"><button class="btn btn-primary">Create Account</button></div></div>
                    </form>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row">
            @foreach([
                ['Dues Paid This Year', $summary['dues_year'], 'info'],
                ['Total Dues on Record', $summary['dues_all_time'], 'success'],
                ['Benefits Received', $summary['benefits_received'], 'primary'],
                ['Pending Benefits', $summary['pending_benefits'], 'warning'],
            ] as [$label, $value, $color])
                <div class="col-md-6"><div class="small-box bg-{{ $color }}"><div class="inner"><h3>{{ number_format($value, 2) }}</h3><p>{{ $label }}</p></div></div></div>
            @endforeach
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ $year }} Annual Dues Matrix</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-bordered">
                    <thead><tr><th>Month</th><th>Expected</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($matrix as $row)
                        <tr class="dues-row-{{ in_array($row['status'], ['paid','overpaid'], true) ? 'paid' : 'unpaid' }}"><td>{{ $row['month'] }}</td><td>{{ number_format($row['expected'], 2) }}</td><td>{{ number_format($row['paid'], 2) }}</td><td>{{ number_format($row['balance'], 2) }}</td><td><strong>{{ str_replace('_', ' ', $row['status']) }}</strong></td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Payment History</h3></div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-sm">
            <thead><tr><th>Date</th><th>Year</th><th>Month</th><th>Amount</th><th>Method</th><th>Reference</th><th>Recorded By</th></tr></thead>
            <tbody>
            @forelse($payments as $payment)
                <tr><td>{{ $payment->payment_date?->format('Y-m-d') }}</td><td>{{ $payment->payment_year }}</td><td>{{ $payment->payment_month }}</td><td>{{ number_format($payment->amount, 2) }}</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->reference_number }}</td><td>{{ $payment->recorder?->name }}</td></tr>
            @empty
                <tr><td colspan="7" class="text-muted">No payments recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $payments->links() }}
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Benefit History</h3></div><div class="card-body table-responsive">
            <table class="table table-sm"><thead><tr><th>Type</th><th>Title</th><th>Status</th><th>Amount</th></tr></thead><tbody>
            @forelse($benefits as $benefit)<tr><td>{{ $benefit->benefitType?->name }}</td><td>{{ $benefit->title }}</td><td>{{ $benefit->status }}</td><td>{{ number_format($benefit->amount, 2) }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No benefits.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Benefit Requests</h3></div><div class="card-body table-responsive">
            <table class="table table-sm"><thead><tr><th>Type</th><th>Subject</th><th>Status</th></tr></thead><tbody>
            @forelse($requests as $request)<tr><td>{{ $request->benefitType?->name }}</td><td>{{ $request->subject }}</td><td>{{ $request->status }}</td></tr>@empty<tr><td colspan="3" class="text-muted">No requests.</td></tr>@endforelse
            </tbody></table>
        </div></div>
    </div>
</div>
@endsection

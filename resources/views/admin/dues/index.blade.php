@extends('layouts.app', ['title' => 'Dues Payment Transactions'])

@section('content')
@php($debugMode = \App\Models\Setting::value('system_mode', 'production') === 'debug')
@if($pendingDeletionRequests->isNotEmpty())
<div class="card card-warning"><div class="card-header"><h3 class="card-title"><i class="fas fa-user-check"></i> Deletion Requests Awaiting Second-Admin Review</h3></div><div class="card-body">
    @foreach($pendingDeletionRequests as $deletionRequest)
    <div class="approval-request">
        <div><strong>{{ $deletionRequest->payment?->staff?->full_name }}</strong> — GHS {{ number_format((float) $deletionRequest->payment?->amount, 2) }} for {{ \App\Services\DuesCalculationService::MONTHS[$deletionRequest->payment?->payment_month] ?? '' }} {{ $deletionRequest->payment?->payment_year }}<br><small>Requested by {{ $deletionRequest->requester?->name }}: {{ $deletionRequest->reason }}</small></div>
        @if($deletionRequest->requested_by === auth()->id())
            <span class="badge badge-secondary">Waiting for another admin</span>
        @else
            <div class="approval-actions">
                <form method="post" action="{{ route('admin.dues.deletion-requests.approve', $deletionRequest) }}" data-prevent-double-submit="true">@csrf<div class="input-group input-group-sm"><input name="password" type="password" class="form-control" placeholder="Your password" required><div class="input-group-append"><button class="btn btn-danger"><i class="fas fa-check"></i> Approve Delete</button></div></div></form>
                <form method="post" action="{{ route('admin.dues.deletion-requests.reject', $deletionRequest) }}" data-prevent-double-submit="true">@csrf<div class="input-group input-group-sm"><input name="password" type="password" class="form-control" placeholder="Your password" required><input name="review_notes" class="form-control" placeholder="Reason (optional)"><div class="input-group-append"><button class="btn btn-outline-secondary">Reject</button></div></div></form>
            </div>
        @endif
    </div>
    @endforeach
</div></div>
@endif
<div class="card">
    <div class="card-body table-responsive">
        <table class="table table-bordered table-sm">
            <thead><tr><th>Date</th><th>Staff</th><th>Year</th><th>Month</th><th>Amount</th><th>Method</th><th>Reference</th><th>Recorded By</th><th></th></tr></thead>
            <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date?->format('Y-m-d') }}</td><td>{{ $payment->staff?->full_name }}</td><td>{{ $payment->payment_year }}</td><td>{{ $payment->payment_month }}</td><td>{{ number_format($payment->amount, 2) }}</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->reference_number }}</td><td>{{ $payment->recorder?->name }}</td>
                    <td>@if($payment->deletionRequests->isNotEmpty() && ! $debugMode)<span class="badge badge-warning">Approval pending</span>@else<button class="btn btn-sm btn-outline-danger" type="button" data-toggle="collapse" data-target="#deletePayment{{ $payment->id }}"><i class="fas fa-trash"></i> {{ $debugMode ? 'Delete Now' : 'Request' }}</button>@endif</td>
                </tr>
                @if($payment->deletionRequests->isEmpty() || $debugMode)<tr class="collapse" id="deletePayment{{ $payment->id }}"><td colspan="9"><form method="post" action="{{ route('admin.dues.deletion-request', $payment) }}" class="deletion-request-form" data-prevent-double-submit="true">@csrf<div><strong>{{ $debugMode ? 'Delete immediately in Debug mode' : 'Request deletion' }}</strong><small class="d-block text-muted">{{ $debugMode ? 'No second-admin approval will be requested. Your action remains audited.' : 'Your password is required. A different administrator must approve before removal.' }}</small></div><input name="reason" class="form-control" placeholder="Reason for deletion" required maxlength="500"><input name="password" type="password" class="form-control" placeholder="Your password" required><button class="btn btn-danger"><i class="fas fa-{{ $debugMode ? 'trash' : 'paper-plane' }}"></i> {{ $debugMode ? 'Delete Payment' : 'Submit Request' }}</button></form></td></tr>@endif
            @endforeach
            </tbody>
        </table>
        {{ $payments->links() }}
    </div>
</div>
@endsection

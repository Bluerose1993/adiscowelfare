@extends('layouts.app', ['title' => 'My Dues'])

@section('content')
<div class="card"><div class="card-body table-responsive">
    <table class="table table-bordered"><thead><tr><th>Month</th><th>Expected</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
    @foreach($matrix as $row)<tr class="dues-row-{{ in_array($row['status'], ['paid','overpaid'], true) ? 'paid' : 'unpaid' }}"><td>{{ $row['month'] }}</td><td>{{ number_format($row['expected'], 2) }}</td><td>{{ number_format($row['paid'], 2) }}</td><td>{{ number_format($row['balance'], 2) }}</td><td><strong>{{ str_replace('_', ' ', $row['status']) }}</strong></td></tr>@endforeach
    </tbody></table>
</div></div>
<div class="card"><div class="card-header"><h3 class="card-title">Payment Details</h3></div><div class="card-body table-responsive">
    <table class="table table-sm"><thead><tr><th>Date</th><th>Year</th><th>Month</th><th>Amount</th><th>Reference</th></tr></thead><tbody>
    @forelse($payments as $payment)<tr><td>{{ $payment->payment_date?->format('Y-m-d') }}</td><td>{{ $payment->payment_year }}</td><td>{{ $payment->payment_month }}</td><td>{{ number_format($payment->amount, 2) }}</td><td>{{ $payment->reference_number }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No dues payments recorded.</td></tr>@endforelse
    </tbody></table>{{ $payments->links() }}
</div></div>
@endsection

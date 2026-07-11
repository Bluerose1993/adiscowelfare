@extends('layouts.app', ['title' => 'My Benefits'])

@section('content')
<div class="card"><div class="card-body table-responsive">
    <table class="table table-bordered"><thead><tr><th>Type</th><th>Title</th><th>Status</th><th>Amount</th><th>Payment Date</th></tr></thead><tbody>
    @forelse($benefits as $benefit)<tr><td>{{ $benefit->benefitType?->name }}</td><td>{{ $benefit->title }}</td><td>{{ $benefit->status }}</td><td>{{ number_format($benefit->amount, 2) }}</td><td>{{ $benefit->payment_date?->format('Y-m-d') }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No benefits recorded.</td></tr>@endforelse
    </tbody></table>{{ $benefits->links() }}
</div></div>
@endsection

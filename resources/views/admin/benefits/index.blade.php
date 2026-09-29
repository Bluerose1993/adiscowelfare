@extends('layouts.app', ['title' => $status === 'pending' ? 'Pending Benefits' : 'All Benefits'])

@section('actions')
@can('manage benefits')<a href="{{ route('admin.benefits.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Record Benefit</a>@endcan
@endsection

@section('content')
<div class="card"><div class="card-header"><form method="get" class="form-inline">
    <label for="benefitStatusFilter" class="mr-2">Review status</label>
    <select id="benefitStatusFilter" name="status" class="form-control mr-2">
        <option value="all" @selected($status === 'all')>All statuses</option>
        <option value="pending" @selected($status === 'pending')>Pending approval or review</option>
        @foreach(['submitted', 'under_review', 'returned', 'approved', 'paid', 'rejected', 'cancelled'] as $item)
            <option value="{{ $item }}" @selected($status === $item)>{{ ucwords(str_replace('_', ' ', $item)) }}</option>
        @endforeach
    </select><button class="btn btn-outline-primary">Filter</button>
</form></div></div>

<div class="card"><div class="card-header"><h3 class="card-title">Staff Benefit Requests</h3></div><div class="card-body table-responsive">
    <table class="table table-bordered table-sm"><thead><tr><th>Submitted</th><th>Staff</th><th>Type</th><th>Request</th><th>Amount</th><th>Review status</th><th>Approved at</th><th>Receipt</th><th></th></tr></thead><tbody>
        @forelse($requests as $requestRecord)
            <tr>
                <td>{{ $requestRecord->submitted_at?->format('d M Y') }}</td>
                <td>{{ $requestRecord->staff?->full_name }}<small class="d-block text-muted">{{ $requestRecord->staff?->staff_id }}</small></td>
                <td>{{ $requestRecord->benefitType?->name }}</td><td>{{ $requestRecord->subject }}</td>
                <td>{{ number_format($requestRecord->approved_amount ?? $requestRecord->requested_amount ?? 0, 2) }}</td>
                <td><span class="badge badge-{{ in_array($requestRecord->status, ['submitted', 'under_review']) ? 'warning' : ($requestRecord->status === 'returned' ? 'info' : (in_array($requestRecord->status, ['approved', 'paid']) ? 'success' : 'secondary')) }}">{{ ucwords(str_replace('_', ' ', $requestRecord->status)) }}</span></td>
                <td>{{ $requestRecord->approved_at?->format('d M Y, H:i') ?: '—' }}</td>
                <td>@if($requestRecord->receipt_confirmed_at){{ number_format($requestRecord->received_amount, 2) }}@if($requestRecord->approved_amount !== null && (float) $requestRecord->received_amount !== (float) $requestRecord->approved_amount)<span class="badge badge-warning ml-1">Differs</span>@endif<small class="d-block text-muted">{{ $requestRecord->receipt_confirmed_at->format('d M Y, H:i') }}</small>@elseif(in_array($requestRecord->status, ['approved', 'paid']))<span class="text-muted">Awaiting confirmation</span>@else<span class="text-muted">—</span>@endif</td>
                <td class="text-nowrap">
                    @can('review benefit requests')<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.benefit-requests.show', $requestRecord) }}" title="View and review"><i class="fas fa-eye"></i></a>@endcan
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.benefit-requests.print', $requestRecord) }}" target="_blank" rel="noopener" title="Print or save PDF"><i class="fas fa-print"></i></a>
                    @can('manage benefits')
                        @if($requestRecord->resultingBenefit)
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.benefits.edit', $requestRecord->resultingBenefit) }}" title="Edit benefit"><i class="fas fa-edit"></i></a>
                            @if($requestRecord->resultingBenefit->status !== 'paid')<form class="d-inline" method="post" action="{{ route('admin.benefits.mark-paid', $requestRecord->resultingBenefit) }}">@csrf<input type="hidden" name="payment_date" value="{{ now()->toDateString() }}"><button class="btn btn-sm btn-outline-success" title="Mark paid"><i class="fas fa-check"></i></button></form>@endif
                            <button class="btn btn-sm btn-outline-danger" data-toggle="collapse" data-target="#deleteLinkedBenefit{{ $requestRecord->id }}" title="Delete benefit"><i class="fas fa-trash"></i></button>
                        @endif
                    @endcan
                </td>
            </tr>
            @can('manage benefits')
                @if($requestRecord->resultingBenefit)
                    <tr class="collapse" id="deleteLinkedBenefit{{ $requestRecord->id }}"><td colspan="9"><form method="post" action="{{ route('admin.benefits.deletion-request', $requestRecord->resultingBenefit) }}" class="deletion-request-form">@csrf<div><strong>Delete this benefit</strong><small class="d-block text-muted">Production mode requires approval from a second admin.</small></div><input name="reason" class="form-control" placeholder="Reason for deletion" required><input name="password" type="password" class="form-control" placeholder="Your password" required><button class="btn btn-danger">Request Delete</button></form></td></tr>
                @endif
            @endcan
        @empty
            <tr><td colspan="9" class="text-center text-muted">No staff requests match this status.</td></tr>
        @endforelse
    </tbody></table>{{ $requests->links() }}
</div></div>

@can('manage benefits')
<div class="card"><div class="card-header">
    <h3 class="card-title">Directly Recorded Benefits</h3>
</div><div class="card-body table-responsive">
    <table class="table table-bordered table-sm">
        <thead><tr><th>Staff</th><th>Type</th><th>Title</th><th>Status</th><th>Amount</th><th>Payment Date</th><th></th></tr></thead>
        <tbody>
        @foreach($benefits as $benefit)
            <tr>
                <td>{{ $benefit->staff?->full_name }}</td><td>{{ $benefit->benefitType?->name }}</td><td>{{ $benefit->title }}</td><td><span class="badge badge-secondary">{{ $benefit->status }}</span></td><td>{{ number_format($benefit->amount, 2) }}</td><td>{{ $benefit->payment_date?->format('Y-m-d') }}</td>
                <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.benefits.edit', $benefit) }}"><i class="fas fa-edit"></i></a>@if($benefit->status !== 'paid')<form class="d-inline" method="post" action="{{ route('admin.benefits.mark-paid', $benefit) }}">@csrf<input type="hidden" name="payment_date" value="{{ now()->toDateString() }}"><button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button></form>@endif <button class="btn btn-sm btn-outline-danger" data-toggle="collapse" data-target="#deleteBenefit{{ $benefit->id }}"><i class="fas fa-trash"></i></button></td>
            </tr>
            <tr class="collapse" id="deleteBenefit{{ $benefit->id }}"><td colspan="7"><form method="post" action="{{ route('admin.benefits.deletion-request', $benefit) }}" class="deletion-request-form">@csrf<div><strong>Delete this benefit</strong><small class="d-block text-muted">Production mode requires approval from a second admin.</small></div><input name="reason" class="form-control" placeholder="Reason for deletion" required><input name="password" type="password" class="form-control" placeholder="Your password" required><button class="btn btn-danger">Request Delete</button></form></td></tr>
        @endforeach
        </tbody>
    </table>
    {{ $benefits->links() }}
</div></div>
@endcan
@endsection

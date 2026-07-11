@extends('layouts.app', ['title' => $status ? ucfirst($status).' Benefits' : 'All Benefits'])

@section('actions')
<a href="{{ route('admin.benefits.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Record Benefit</a>
@endsection

@section('content')
@if($pendingDeletionRequests->isNotEmpty())<div class="card card-warning"><div class="card-header"><h3 class="card-title">Pending Benefit Deletions</h3></div><div class="card-body">@foreach($pendingDeletionRequests as $deletion)<div class="approval-request"><div><strong>{{ $deletion->benefit?->staff?->full_name }} — {{ $deletion->benefit?->title }}</strong><small class="d-block">Requested by {{ $deletion->requester?->name }}: {{ $deletion->reason }}</small></div><form method="post" action="{{ route('admin.benefits.deletion-requests.approve', $deletion) }}">@csrf<div class="input-group"><input type="password" name="password" class="form-control" placeholder="Approver password" required><div class="input-group-append"><button class="btn btn-danger">Approve Delete</button></div></div></form><form method="post" action="{{ route('admin.benefits.deletion-requests.reject', $deletion) }}">@csrf<input type="hidden" name="review_notes" value="Rejected by reviewing administrator"><div class="input-group"><input type="password" name="password" class="form-control" placeholder="Approver password" required><div class="input-group-append"><button class="btn btn-outline-secondary">Reject</button></div></div></form></div>@endforeach</div></div>@endif
<div class="card"><div class="card-header">
    <form method="get" class="form-inline"><select name="status" class="form-control mr-2"><option value="">All statuses</option>@foreach(['pending','approved','paid','rejected','cancelled'] as $item)<option value="{{ $item }}" @selected($status === $item)>{{ ucfirst($item) }}</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form>
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
@endsection

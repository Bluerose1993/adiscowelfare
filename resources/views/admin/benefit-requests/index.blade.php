@extends('layouts.app', ['title' => 'Benefit Requests'])

@section('content')
@if($pendingDeletionRequests->isNotEmpty())<div class="card card-warning"><div class="card-header"><h3 class="card-title">Pending Benefit Request Deletions</h3></div><div class="card-body">@foreach($pendingDeletionRequests as $deletion)<div class="approval-request"><div><strong>{{ $deletion->benefitRequest?->staff?->full_name }} — {{ $deletion->benefitRequest?->subject }}</strong><small class="d-block">Requested by {{ $deletion->requester?->name }}: {{ $deletion->reason }}</small></div><form method="post" action="{{ route('admin.benefit-requests.deletion-requests.approve', $deletion) }}">@csrf<div class="input-group"><input type="password" name="password" class="form-control" placeholder="Approver password" required><div class="input-group-append"><button class="btn btn-danger">Approve Delete</button></div></div></form><form method="post" action="{{ route('admin.benefit-requests.deletion-requests.reject', $deletion) }}">@csrf<div class="input-group"><input type="password" name="password" class="form-control" placeholder="Approver password" required><div class="input-group-append"><button class="btn btn-outline-secondary">Reject</button></div></div></form></div>@endforeach</div></div>@endif
<div class="card"><div class="card-header">
    <form method="get" class="form-inline"><select name="status" class="form-control mr-2"><option value="">All statuses</option>@foreach(['submitted','under_review','approved','rejected','cancelled','paid'] as $item)<option value="{{ $item }}" @selected($status === $item)>{{ str_replace('_', ' ', ucfirst($item)) }}</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form>
</div><div class="card-body table-responsive">
    <table class="table table-bordered table-sm">
        <thead><tr><th>Date</th><th>Staff</th><th>Type</th><th>Subject</th><th>Requested</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($requests as $request)
            <tr><td>{{ $request->submitted_at?->format('Y-m-d') }}</td><td>{{ $request->staff?->full_name }}</td><td>{{ $request->benefitType?->name }}</td><td>{{ $request->subject }}</td><td>{{ $request->requested_amount ? number_format($request->requested_amount, 2) : '-' }}</td><td><span class="badge badge-info">{{ str_replace('_', ' ', $request->status) }}</span></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.benefit-requests.show', $request) }}"><i class="fas fa-eye"></i></a> <button class="btn btn-sm btn-outline-danger" data-toggle="collapse" data-target="#deleteRequest{{ $request->id }}"><i class="fas fa-trash"></i></button></td></tr>
            <tr class="collapse" id="deleteRequest{{ $request->id }}"><td colspan="7"><form method="post" action="{{ route('admin.benefit-requests.deletion-request', $request) }}" class="deletion-request-form">@csrf<div><strong>Delete this benefit request</strong><small class="d-block text-muted">Production mode requires a second administrator.</small></div><input name="reason" class="form-control" placeholder="Reason for deletion" required><input name="password" type="password" class="form-control" placeholder="Your password" required><button class="btn btn-danger">Request Delete</button></form></td></tr>
        @endforeach
        </tbody>
    </table>
    {{ $requests->links() }}
</div></div>
@endsection

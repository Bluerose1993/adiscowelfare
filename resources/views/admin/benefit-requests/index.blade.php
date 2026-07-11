@extends('layouts.app', ['title' => 'Benefit Requests'])

@section('content')
<div class="card"><div class="card-header">
    <form method="get" class="form-inline"><select name="status" class="form-control mr-2"><option value="">All statuses</option>@foreach(['submitted','under_review','approved','rejected','cancelled','paid'] as $item)<option value="{{ $item }}" @selected($status === $item)>{{ str_replace('_', ' ', ucfirst($item)) }}</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form>
</div><div class="card-body table-responsive">
    <table class="table table-bordered table-sm">
        <thead><tr><th>Date</th><th>Staff</th><th>Type</th><th>Subject</th><th>Requested</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($requests as $request)
            <tr><td>{{ $request->submitted_at?->format('Y-m-d') }}</td><td>{{ $request->staff?->full_name }}</td><td>{{ $request->benefitType?->name }}</td><td>{{ $request->subject }}</td><td>{{ $request->requested_amount ? number_format($request->requested_amount, 2) : '-' }}</td><td><span class="badge badge-info">{{ str_replace('_', ' ', $request->status) }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.benefit-requests.show', $request) }}"><i class="fas fa-eye"></i></a></td></tr>
        @endforeach
        </tbody>
    </table>
    {{ $requests->links() }}
</div></div>
@endsection

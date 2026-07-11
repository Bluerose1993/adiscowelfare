@extends('layouts.app', ['title' => 'My Benefit Requests'])

@section('actions')
<a href="{{ route('staff.requests.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Submit Request</a>
@endsection

@section('content')
<div class="card"><div class="card-body table-responsive">
    <table class="table table-bordered"><thead><tr><th>Date</th><th>Type</th><th>Subject</th><th>Status</th><th></th></tr></thead><tbody>
    @foreach($requests as $request)<tr><td>{{ $request->submitted_at?->format('Y-m-d') }}</td><td>{{ $request->benefitType?->name }}</td><td>{{ $request->subject }}</td><td>{{ str_replace('_', ' ', $request->status) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('staff.requests.show', $request) }}"><i class="fas fa-eye"></i></a></td></tr>@endforeach
    </tbody></table>{{ $requests->links() }}
</div></div>
@endsection

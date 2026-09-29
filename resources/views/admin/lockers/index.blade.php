@extends('layouts.app', ['title' => 'Locker Assignments'])

@section('content')
@if($pendingRequests->isNotEmpty())<div class="card card-warning"><div class="card-header"><h3 class="card-title">Pending Locker Applications</h3></div><div class="card-body">@foreach($pendingRequests as $lockerRequest)<div class="approval-request"><div><strong>{{ $lockerRequest->staff?->full_name }}</strong><small class="d-block">Preferred locker: {{ $lockerRequest->preferred_locker_number ?: 'No preference' }}</small><small>{{ $lockerRequest->notes }}</small></div><div class="approval-actions"><form method="post" action="{{ route('admin.lockers.approve', $lockerRequest) }}">@csrf<div class="input-group"><input name="locker_number" class="form-control" value="{{ $lockerRequest->preferred_locker_number }}" placeholder="Locker number" required><input name="review_notes" class="form-control" placeholder="Approval note (optional)"><div class="input-group-append"><button class="btn btn-success">Approve &amp; Assign</button></div></div></form><form method="post" action="{{ route('admin.lockers.reject', $lockerRequest) }}">@csrf<div class="input-group"><input name="review_notes" class="form-control" placeholder="Reason for rejection" required><div class="input-group-append"><button class="btn btn-outline-danger">Reject</button></div></div></form></div></div>@endforeach</div></div>@endif
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <form method="get" action="{{ route('admin.lockers.index') }}" class="form-inline mb-2 mb-lg-0">
            <input name="search" class="form-control mr-2 mb-2 mb-sm-0" value="{{ $search }}" placeholder="Search staff, ID or phone" aria-label="Search staff, ID or phone">
            <label for="lockerAssignmentFilter" class="mr-2">Locker status</label>
            <select id="lockerAssignmentFilter" name="assignment" class="form-control mr-2 mb-2 mb-sm-0">
                <option value="all" @selected($assignment === 'all')>All staff</option>
                <option value="assigned" @selected($assignment === 'assigned')>With locker numbers</option>
                <option value="unassigned" @selected($assignment === 'unassigned')>Without locker numbers</option>
            </select>
            <button class="btn btn-outline-primary">Filter</button>
        </form>
        <div class="btn-group mb-2 mb-lg-0" role="group" aria-label="Locker register exports">
            <a class="btn btn-outline-success" href="{{ route('admin.lockers.export', ['search' => $search, 'assignment' => $assignment]) }}"><i class="fas fa-file-excel mr-1"></i> Export Excel</a>
            <a class="btn btn-outline-danger" target="_blank" rel="noopener" href="{{ route('admin.lockers.print', ['search' => $search, 'assignment' => $assignment]) }}"><i class="fas fa-file-pdf mr-1"></i> Print / Save PDF</a>
        </div>
    </div>
    <div class="card-body table-responsive">
        <div class="text-muted mb-2">{{ number_format($staff->total()) }} matching staff record{{ $staff->total() === 1 ? '' : 's' }}</div>
        <table class="table table-bordered table-hover">
            <thead><tr><th>Staff ID</th><th>Staff</th><th>Department</th><th>Locker Number</th><th>Assign / Change</th></tr></thead>
            <tbody>
                @forelse($staff as $member)
                    <tr><td>{{ $member->staff_id }}</td><td><a href="{{ route('admin.staff.show', $member) }}">{{ $member->full_name }}</a></td><td>{{ $member->department }}</td><td><strong>{{ $member->locker_number ?: 'Not assigned' }}</strong></td><td><form method="post" action="{{ route('admin.lockers.assign', $member) }}" class="form-inline">@csrf @method('PUT')<input name="locker_number" class="form-control form-control-sm mr-2" value="{{ $member->locker_number }}" placeholder="Locker number"><button class="btn btn-sm btn-primary">Save</button></form></td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No staff match this locker filter.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $staff->links() }}
    </div>
</div>
@endsection

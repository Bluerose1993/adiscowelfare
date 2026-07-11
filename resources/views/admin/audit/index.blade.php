@extends('layouts.app', ['title' => 'Audit Logs'])

@section('content')
<div class="card"><div class="card-body table-responsive">
    <table class="table table-bordered table-sm"><thead><tr><th>Date</th><th>User</th><th>Action</th><th>Record</th><th>IP</th></tr></thead><tbody>
    @foreach($logs as $log)<tr><td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td><td>{{ $log->user?->name ?: 'System' }}</td><td>{{ $log->action }}</td><td>{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}</td><td>{{ $log->ip_address }}</td></tr>@endforeach
    </tbody></table>{{ $logs->links() }}
</div></div>
@endsection

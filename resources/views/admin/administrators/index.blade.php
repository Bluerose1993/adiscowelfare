@extends('layouts.app', ['title' => 'Administrator Access'])

@section('actions')
<a href="{{ route('admin.administrators.create') }}" class="btn btn-primary"><i class="fas fa-user-shield"></i> Add Administrator</a>
@endsection

@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Administrator Accounts</h3></div><div class="card-body table-responsive">
    <table class="table table-hover"><thead><tr><th>Name</th><th>Username</th><th>Status</th><th>Visible system options</th><th>Last login</th><th></th></tr></thead><tbody>
    @foreach($administrators as $administrator)
        <tr><td><strong>{{ $administrator->name }}</strong><br><small class="text-muted">{{ $administrator->email }}</small></td><td>{{ $administrator->username }}</td><td><span class="badge badge-{{ $administrator->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($administrator->status) }}</span></td><td><div class="permission-chips">@forelse($administrator->permissions as $permission)<span>{{ ucwords($permission->name) }}</span>@empty<em class="text-muted">No options assigned</em>@endforelse</div></td><td>{{ $administrator->last_login_at?->format('Y-m-d H:i') ?: 'Never' }}</td><td class="text-nowrap"><a href="{{ route('admin.administrators.edit', $administrator) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-sliders-h"></i> Access</a>@unless($administrator->is(auth()->user()))<form method="post" action="{{ route('admin.administrators.toggle-status', $administrator) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary"><i class="fas fa-power-off"></i></button></form>@endunless</td></tr>
    @endforeach
    </tbody></table>
    {{ $administrators->links() }}
</div></div>
@endsection

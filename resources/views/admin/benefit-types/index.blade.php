@extends('layouts.app', ['title' => 'Benefit Types'])

@section('actions')
<a href="{{ route('admin.benefit-types.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Add Type</a>
@endsection

@section('content')
<div class="card"><div class="card-body table-responsive">
    <table class="table table-bordered datatable">
        <thead><tr><th>Name</th><th>Default Amount</th><th>Approval</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @foreach($benefitTypes as $type)
            <tr><td>{{ $type->name }}</td><td>{{ $type->default_amount ? number_format($type->default_amount, 2) : '-' }}</td><td>{{ $type->requires_approval ? 'Required' : 'Not required' }}</td><td><span class="badge badge-{{ $type->is_active ? 'success' : 'secondary' }}">{{ $type->is_active ? 'Active' : 'Inactive' }}</span></td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.benefit-types.edit', $type) }}"><i class="fas fa-edit"></i></a></td></tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection

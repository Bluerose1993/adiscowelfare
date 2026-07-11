@extends('layouts.app', ['title' => $status ? ucfirst($status).' Benefits' : 'All Benefits'])

@section('actions')
<a href="{{ route('admin.benefits.create') }}" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Record Benefit</a>
@endsection

@section('content')
<div class="card"><div class="card-header">
    <form method="get" class="form-inline"><select name="status" class="form-control mr-2"><option value="">All statuses</option>@foreach(['pending','approved','paid','rejected','cancelled'] as $item)<option value="{{ $item }}" @selected($status === $item)>{{ ucfirst($item) }}</option>@endforeach</select><button class="btn btn-outline-primary">Filter</button></form>
</div><div class="card-body table-responsive">
    <table class="table table-bordered table-sm">
        <thead><tr><th>Staff</th><th>Type</th><th>Title</th><th>Status</th><th>Amount</th><th>Payment Date</th><th></th></tr></thead>
        <tbody>
        @foreach($benefits as $benefit)
            <tr>
                <td>{{ $benefit->staff?->full_name }}</td><td>{{ $benefit->benefitType?->name }}</td><td>{{ $benefit->title }}</td><td><span class="badge badge-secondary">{{ $benefit->status }}</span></td><td>{{ number_format($benefit->amount, 2) }}</td><td>{{ $benefit->payment_date?->format('Y-m-d') }}</td>
                <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.benefits.edit', $benefit) }}"><i class="fas fa-edit"></i></a>@if($benefit->status !== 'paid')<form class="d-inline" method="post" action="{{ route('admin.benefits.mark-paid', $benefit) }}">@csrf<input type="hidden" name="payment_date" value="{{ now()->toDateString() }}"><button class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></button></form>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $benefits->links() }}
</div></div>
@endsection

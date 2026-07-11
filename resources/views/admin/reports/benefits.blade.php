@extends('layouts.app', ['title' => 'Benefits Report'])

@section('actions')
<a class="btn btn-sm btn-success" href="{{ route('admin.exports.benefits', request()->query()) }}"><i class="fas fa-file-excel"></i> Annual Benefits Chart</a>
@endsection

@section('content')
<form method="get" class="card card-body mb-3"><div class="form-row align-items-end">
    <div class="col-md-2"><label>Year</label><input name="year" type="number" class="form-control" value="{{ request('year') }}"></div>
    <div class="col-md-3"><label>Benefit Type</label><select name="benefit_type_id" class="form-control"><option value="">All</option>@foreach($benefitTypes as $type)<option value="{{ $type->id }}" @selected(request('benefit_type_id') == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label>Staff</label><select name="staff_id" class="form-control"><option value="">All</option>@foreach($staff as $member)<option value="{{ $member->id }}" @selected(request('staff_id') == $member->id)>{{ $member->full_name }}</option>@endforeach</select></div>
    <div class="col-md-2"><label>Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['pending','approved','paid','rejected','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary btn-block">Filter</button></div>
</div></form>
<div class="card"><div class="card-body table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Staff</th><th>Type</th><th>Title</th><th>Status</th><th>Amount</th></tr></thead><tbody>
@foreach($benefits as $benefit)<tr><td>{{ $benefit->staff?->full_name }}</td><td>{{ $benefit->benefitType?->name }}</td><td>{{ $benefit->title }}</td><td>{{ $benefit->status }}</td><td>{{ number_format($benefit->amount, 2) }}</td></tr>@endforeach
</tbody></table>{{ $benefits->links() }}</div></div>
@endsection

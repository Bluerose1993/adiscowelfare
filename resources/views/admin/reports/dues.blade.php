@extends('layouts.app', ['title' => 'Dues Report'])

@section('actions')
<a class="btn btn-sm btn-success" href="{{ route('admin.exports.annual-dues-chart', request()->only('year')) }}"><i class="fas fa-file-excel"></i> Annual Chart</a>
@endsection

@section('content')
<form method="get" class="card card-body mb-3"><div class="form-row align-items-end">
    <div class="col-md-2"><label>Year</label><input name="year" type="number" class="form-control" value="{{ $year }}"></div>
    <div class="col-md-3"><label>Month</label><select name="month" class="form-control"><option value="">Full year</option>@foreach($months as $number => $name)<option value="{{ $number }}" @selected($month == $number)>{{ $name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label>Department</label><select name="department" class="form-control"><option value="">All</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>@endforeach</select></div>
    <div class="col-md-2"><label>Status</label><select name="status" class="form-control"><option value="">All</option>@foreach(['paid','partially_paid','unpaid','overpaid'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str_replace('_',' ', $status) }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary btn-block">Filter</button></div>
</div></form>
<div class="card"><div class="card-body table-responsive"><table class="table table-bordered datatable"><thead><tr><th>Staff ID</th><th>Name</th><th>Expected</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
@foreach($rows as $row)<tr><td>{{ $row['staff']->staff_id }}</td><td>{{ $row['staff']->full_name }}</td><td>{{ number_format($row['expected'], 2) }}</td><td>{{ number_format($row['paid'], 2) }}</td><td>{{ number_format($row['balance'], 2) }}</td><td>{{ str_replace('_', ' ', $row['status']) }}</td></tr>@endforeach
</tbody></table></div></div>
@endsection

@extends('layouts.app', ['title' => 'Benefits Expense Report'])

@section('actions')
<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reports.benefits.print', $filters) }}" target="_blank" rel="noopener"><i class="fas fa-print"></i> Print / Save PDF</a>
<a class="btn btn-sm btn-success" href="{{ route('admin.exports.benefits', request()->query()) }}"><i class="fas fa-file-excel"></i> Annual Benefits Chart</a>
@endsection

@section('content')
<form method="get" class="card card-body mb-3"><div class="form-row align-items-end">
    <div class="col-md-2"><label>Payment Year</label><input name="year" type="number" min="2000" max="2100" class="form-control" value="{{ $filters['year'] ?? '' }}" placeholder="All years"></div>
    <div class="col-md-3"><label>Benefit Type</label><select name="benefit_type_id" class="form-control"><option value="">All</option>@foreach($benefitTypes as $type)<option value="{{ $type->id }}" @selected(request('benefit_type_id') == $type->id)>{{ $type->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label>Staff</label><select name="staff_id" class="form-control"><option value="">All</option>@foreach($staff as $member)<option value="{{ $member->id }}" @selected(request('staff_id') == $member->id)>{{ $member->full_name }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary btn-block">Filter Paid Expenses</button></div>
</div></form>
<div class="card card-outline card-primary"><div class="card-body d-flex flex-wrap justify-content-between align-items-center">
    <div><div class="text-muted">Total paid expenses across all matching records</div><strong class="h3 mb-0">GHS {{ number_format($totalPaid, 2) }}</strong></div>
    <div class="text-muted">{{ number_format($expenses->total()) }} paid transaction{{ $expenses->total() === 1 ? '' : 's' }}</div>
</div></div>
<div class="card"><div class="card-body table-responsive"><table class="table table-bordered table-sm"><thead><tr><th>Payment Date</th><th>Staff ID</th><th>Staff</th><th>Type</th><th>Expense</th><th>Source</th><th class="text-right">Amount (GHS)</th></tr></thead><tbody>
@forelse($expenses as $expense)
    <tr><td>{{ $expense->expense_date ?: '—' }}</td><td>{{ $expense->staff_code }}</td><td>{{ $expense->staff_name }}</td><td>{{ $expense->benefit_type }}</td><td>{{ $expense->title }}</td><td>{{ $expense->source === 'request' ? 'Paid request' : 'Direct benefit' }} #{{ $expense->record_id }}</td><td class="text-right text-nowrap">{{ number_format((float) $expense->amount, 2) }}</td></tr>
@empty
    <tr><td colspan="7" class="text-center text-muted">No paid benefit expenses match these filters.</td></tr>
@endforelse
</tbody><tfoot><tr class="font-weight-bold"><td colspan="6" class="text-right">TOTAL PAID (ALL MATCHING RECORDS)</td><td class="text-right text-nowrap" style="border-top:2px solid #172033;border-bottom:3px double #172033">GHS {{ number_format($totalPaid, 2) }}</td></tr></tfoot></table>{{ $expenses->links() }}</div></div>
@endsection

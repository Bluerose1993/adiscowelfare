@extends('layouts.app', ['title' => 'Bulk Dues Entry'])

@section('content')
<form method="get" class="card card-body mb-3">
    <div class="form-row align-items-end">
        <div class="col-md-3"><label>Year</label><input name="payment_year" type="number" class="form-control" value="{{ $year }}"></div>
        <div class="col-md-3"><label>Month</label><select name="payment_month" class="form-control">@foreach($months as $number => $name)<option value="{{ $number }}" @selected($month == $number)>{{ $name }}</option>@endforeach</select></div>
        <div class="col-md-3"><button class="btn btn-outline-primary"><i class="fas fa-sync"></i> Load</button></div>
    </div>
</form>
<form method="post" action="{{ route('admin.dues.bulk.store') }}" data-prevent-double-submit="true">
    @csrf
    <input type="hidden" name="payment_year" value="{{ $year }}">
    <input type="hidden" name="payment_month" value="{{ $month }}">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Expected monthly dues: {{ number_format($expected, 2) }}</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered datatable">
                <thead><tr><th>Staff ID</th><th>Name</th><th>Expected</th><th>Already Paid</th><th>New Amount</th><th>Payment Date</th><th>Reference</th></tr></thead>
                <tbody>
                @foreach($staff as $index => $member)
                    <tr class="dues-row-{{ (float) ($paidByStaff[$member->id] ?? 0) >= $expected && $expected > 0 ? 'paid' : 'unpaid' }}">
                        <td>{{ $member->staff_id }}<input type="hidden" name="payments[{{ $index }}][staff_id]" value="{{ $member->id }}"></td>
                        <td>{{ $member->full_name }}</td>
                        <td>{{ number_format($expected, 2) }}</td>
                        <td>{{ number_format((float) ($paidByStaff[$member->id] ?? 0), 2) }}</td>
                        <td><input name="payments[{{ $index }}][amount]" type="number" step="0.01" min="0.01" class="form-control form-control-sm"></td>
                        <td><input name="payments[{{ $index }}][payment_date]" type="date" class="form-control form-control-sm" value="{{ now()->toDateString() }}"></td>
                        <td><input name="payments[{{ $index }}][reference_number]" class="form-control form-control-sm"></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer"><button class="btn btn-success"><i class="fas fa-save"></i> Save Entered Payments</button></div>
    </div>
</form>
@endsection

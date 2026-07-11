@extends('layouts.app', ['title' => 'Staff Statement'])

@section('actions')
<button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
<a class="btn btn-sm btn-success" href="{{ route('admin.exports.staff-statement', [$staff, 'year' => $year]) }}"><i class="fas fa-file-excel"></i> Excel</a>
@endsection

@section('content')
<div class="card"><div class="card-body">
    <h4>{{ $staff->full_name }}</h4>
    <p>Staff ID: {{ $staff->staff_id }} | Department: {{ $staff->department }} | Year: {{ $year }}</p>
    <table class="table table-bordered"><thead><tr><th>Month</th><th>Expected</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
    @foreach($matrix as $row)<tr class="dues-row-{{ in_array($row['status'], ['paid','overpaid'], true) ? 'paid' : 'unpaid' }}"><td>{{ $row['month'] }}</td><td>{{ number_format($row['expected'], 2) }}</td><td>{{ number_format($row['paid'], 2) }}</td><td>{{ number_format($row['balance'], 2) }}</td><td><strong>{{ str_replace('_', ' ', $row['status']) }}</strong></td></tr>@endforeach
    </tbody></table>
</div></div>
@endsection

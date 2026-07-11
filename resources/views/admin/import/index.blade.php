@extends('layouts.app', ['title' => 'Import Staff/Dues'])

@section('content')
@can('manage staff')
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Bulk Add Staff</h3></div>
    <form method="post" action="{{ route('admin.staff.import') }}" enctype="multipart/form-data" data-prevent-double-submit="true">
        @csrf
        <div class="card-body">
            <p class="text-muted">Upload an Excel file containing <strong>NAME</strong>, <strong>STAFF ID</strong>, and <strong>PHONE NUMBER</strong>. Username will be the staff ID and the initial password will be the phone number. Staff must change it on first login.</p>
            <div class="form-group mb-0"><label>Staff Excel File</label><input name="file" type="file" class="form-control-file" accept=".xlsx,.xls,.csv" required></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary"><i class="fas fa-users"></i> Import Staff</button></div>
    </form>
</div>
@if(session('staff_import_summary.errors'))
    <div class="card card-warning">
        <div class="card-header"><h3 class="card-title">Rows Needing Attention</h3></div>
        <div class="card-body"><ul class="mb-0">@foreach(session('staff_import_summary.errors') as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    </div>
@endif
@endcan
@can('manage dues')
<h4 class="mb-3">Bulk Dues Import</h4>
<form method="post" action="{{ route('admin.import.preview') }}" enctype="multipart/form-data" class="card card-body" data-prevent-double-submit="true">
    @csrf
    <p class="text-muted">Upload the annual dues chart with a member-name column, January–December columns and total payment. The year entered below must match the year in the workbook title.</p>
    <div class="form-row align-items-end">
        <div class="col-md-3"><label>Import Year</label><input name="year" type="number" class="form-control" value="{{ now()->year }}" required></div>
        <div class="col-md-6"><label>Excel File</label><input name="file" type="file" class="form-control-file" required></div>
        <div class="col-md-3"><button class="btn btn-primary"><i class="fas fa-upload"></i> Preview Import</button></div>
    </div>
</form>
<div class="card"><div class="card-header"><h3 class="card-title">Import History</h3></div><div class="card-body table-responsive">
    <table class="table table-sm"><thead><tr><th>Date</th><th>File</th><th>Status</th><th>Created</th><th>Payments</th><th>Manual Review</th><th></th></tr></thead><tbody>
    @foreach($batches as $batch)<tr><td>{{ $batch->created_at->format('Y-m-d H:i') }}</td><td>{{ $batch->original_filename }}</td><td>{{ $batch->status }}</td><td>{{ $batch->staff_created }}</td><td>{{ $batch->payments_created }}</td><td>{{ $batch->manual_review_count }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.import.show', $batch) }}">Open</a></td></tr>@endforeach
    </tbody></table>{{ $batches->links() }}
</div></div>
@endcan
@endsection

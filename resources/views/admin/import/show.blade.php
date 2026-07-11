@extends('layouts.app', ['title' => 'Import Preview'])

@section('actions')
@if($batch->status !== 'committed')
<form class="d-inline" method="post" action="{{ route('admin.import.commit', $batch) }}">@csrf<button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Commit Safe Rows</button></form>
@endif
@endsection

@section('content')
@if($batch->manual_review_count)
<div class="alert alert-warning"><strong>{{ $batch->manual_review_count }} row(s) need attention.</strong> Safe rows can be committed now. Resolve the remaining rows below and commit again.</div>
@endif
<div class="card"><div class="card-body table-responsive">
    <table class="table table-bordered table-sm"><thead><tr><th>Row</th><th>Staff ID</th><th>Name</th><th>Match</th><th>Issue</th><th>Total</th><th></th></tr></thead><tbody>
    @foreach($batch->rows as $row)
        <tr class="{{ $row->status === 'manual_review' ? 'dues-row-unpaid' : '' }}">
            <td>{{ $row->row_number }}</td><td>{{ $row->staff_id }}</td><td>{{ $row->full_name }}</td><td>{{ $row->matchedStaff?->full_name ?: str_replace('_',' ', $row->status) }}</td><td>{{ $row->message }}</td><td>{{ number_format((float) $row->reported_total, 2) }}</td>
            <td>@if($row->status === 'manual_review')<button class="btn btn-sm btn-warning" type="button" data-toggle="collapse" data-target="#resolveRow{{ $row->id }}"><i class="fas fa-tools"></i> Review &amp; Resolve</button>@else<span class="badge badge-success"><i class="fas fa-check"></i> Ready</span>@endif</td>
        </tr>
        @if($row->status === 'manual_review')
        <tr class="collapse" id="resolveRow{{ $row->id }}"><td colspan="7" class="p-3">
            <div class="import-resolution-panel">
                <h5><i class="fas fa-exclamation-triangle text-warning"></i> What needs fixing?</h5>
                <p class="mb-2">{{ $row->message }}</p>
                <div class="solution-note"><strong>Possible solution:</strong> Select the correct staff member below. If the spreadsheet values are wrong, adjust any month before saving the resolution.</div>
                <form method="post" action="{{ route('admin.import.rows.resolve', [$batch, $row]) }}" data-prevent-double-submit="true">
                    @csrf
                    <div class="form-group mt-3"><label class="required">Match to staff member</label><select name="matched_staff_id" class="form-control" required><option value="">Choose staff...</option>@foreach($staffMembers as $member)<option value="{{ $member->id }}">{{ $member->staff_id ?: 'No ID' }} — {{ $member->full_name }}</option>@endforeach</select></div>
                    <div class="month-grid import-month-grid">@foreach(\App\Services\DuesCalculationService::MONTHS as $number => $name)<div><label>{{ substr($name,0,3) }}</label><input name="monthly_amounts[{{ $number }}]" type="number" step="0.01" min="0" class="form-control" value="{{ number_format((float) ($row->monthly_amounts[$number] ?? 0), 2, '.', '') }}"></div>@endforeach</div>
                    <button class="btn btn-primary mt-3"><i class="fas fa-check"></i> Save Resolution</button>
                </form>
            </div>
        </td></tr>
        @endif
    @endforeach
    </tbody></table>
</div></div>
@endsection

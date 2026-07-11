<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\Staff;
use App\Services\AuditService;
use App\Services\ExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        return view('admin.import.index', [
            'batches' => ImportBatch::query()->with('uploader')->latest()->paginate(20),
        ]);
    }

    public function preview(Request $request, ExcelImportService $importer, AuditService $audit): RedirectResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $batch = $importer->preview($request->file('file'), (int) $request->integer('year'), $request->user()->id);
        } catch (\RuntimeException $exception) {
            return back()->withInput()->withErrors(['file' => $exception->getMessage()]);
        }
        $audit->log('excel_import_previewed', $batch, [], $batch->toArray());

        return redirect()->route('admin.import.show', $batch)->with('success', 'Import preview prepared.');
    }

    public function show(ImportBatch $importBatch): View
    {
        return view('admin.import.show', [
            'batch' => $importBatch->load('rows.matchedStaff'),
            'staffMembers' => Staff::query()->active()->orderBy('full_name')->get(['id', 'staff_id', 'full_name']),
        ]);
    }

    public function resolve(Request $request, ImportBatch $importBatch, ImportBatchRow $row, AuditService $audit): RedirectResponse
    {
        abort_unless($row->import_batch_id === $importBatch->id, 404);
        abort_unless($row->status === 'manual_review', 422, 'Only unresolved manual-review rows can be changed.');

        $validated = $request->validate([
            'matched_staff_id' => ['required', 'integer', 'exists:staff,id'],
            'monthly_amounts' => ['required', 'array'],
            'monthly_amounts.*' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ]);
        $monthly = collect(range(1, 12))->mapWithKeys(fn ($month) => [
            $month => round((float) ($validated['monthly_amounts'][$month] ?? 0), 2),
        ])->all();
        $old = $row->toArray();
        $staff = Staff::query()->findOrFail($validated['matched_staff_id']);
        $row->update([
            'matched_staff_id' => $staff->id,
            'monthly_amounts' => $monthly,
            'reported_total' => array_sum($monthly),
            'status' => 'matched',
            'message' => 'Manually matched to '.$staff->full_name.' by '.$request->user()->name.'.',
        ]);
        $importBatch->update([
            'status' => 'review_required',
            'manual_review_count' => $importBatch->rows()->where('status', 'manual_review')->count(),
        ]);
        $audit->log('excel_import_row_resolved', $row, $old, $row->fresh()->toArray(), $request);

        return back()->with('success', "Row {$row->row_number} resolved. Review the amounts, then commit safe rows.");
    }

    public function commit(ImportBatch $importBatch, ExcelImportService $importer, AuditService $audit): RedirectResponse
    {
        $summary = $importer->commit($importBatch, auth()->id());
        $audit->log('excel_import_committed', $importBatch, [], $summary);

        return redirect()->route('admin.import.show', $importBatch)->with('success', 'Import completed.');
    }
}

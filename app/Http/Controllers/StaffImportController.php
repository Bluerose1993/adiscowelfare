<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\StaffExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StaffImportController extends Controller
{
    public function store(Request $request, StaffExcelImportService $importer, AuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $summary = $importer->import($validated['file']);
        $audit->log('staff_excel_imported', null, [], array_diff_key($summary, ['errors' => true]), $request);

        return back()->with('success', "Staff import completed: {$summary['staff_created']} created, {$summary['staff_updated']} updated, {$summary['accounts_created']} accounts created, {$summary['skipped']} skipped.")
            ->with('staff_import_summary', $summary);
    }
}

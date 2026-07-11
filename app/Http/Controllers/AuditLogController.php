<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('admin.audit.index', [
            'logs' => AuditLog::query()->with('user')->latest()->paginate(50),
        ]);
    }
}

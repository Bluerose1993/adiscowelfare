<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\BenefitRequestController;
use App\Http\Controllers\BenefitTypeController;
use App\Http\Controllers\BulkDuesPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DuesPaymentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffImportController;
use App\Http\Controllers\StaffPortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        if (! auth()->user()->hasRole('Administrator')) {
            return redirect()->route('staff.dashboard');
        }
        foreach (['view dashboard' => 'admin.dashboard', 'manage staff' => 'admin.staff.index', 'manage dues' => 'admin.dues.record', 'manage benefits' => 'admin.benefits.index', 'review benefit requests' => 'admin.benefit-requests.index', 'view reports' => 'admin.reports.dues', 'manage administrators' => 'admin.administrators.index', 'manage settings' => 'admin.settings.index', 'view audit logs' => 'admin.audit.index'] as $permission => $route) {
            if (auth()->user()->can($permission)) {
                return redirect()->route($route);
            }
        }
        abort(403, 'No system options have been assigned to this administrator account.');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/session/keep-alive', fn () => response()->json(['active' => true, 'renewed_at' => now()->timestamp]))->middleware('auth')->name('session.keep-alive');

Route::middleware(['auth', 'role:Administrator', 'admin.module'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('administrators', AdminUserController::class)->except(['show', 'destroy']);
    Route::post('administrators/{administrator}/reset-password', [AdminUserController::class, 'resetPassword'])->name('administrators.reset-password');
    Route::post('administrators/{administrator}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('administrators.toggle-status');

    Route::resource('staff', StaffController::class)->except(['destroy']);
    Route::delete('staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
    Route::post('staff/{staff}/create-account', [StaffController::class, 'createAccount'])->name('staff.create-account');
    Route::post('staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');
    Route::post('staff/{staff}/deletion-request', [StaffController::class, 'requestDeletion'])->name('staff.deletion-request');
    Route::post('staff/deletion-requests/{deletionRequest}/approve', [StaffController::class, 'approveDeletion'])->name('staff.deletion-requests.approve');
    Route::post('staff/deletion-requests/{deletionRequest}/reject', [StaffController::class, 'rejectDeletion'])->name('staff.deletion-requests.reject');
    Route::post('staff/import', [StaffImportController::class, 'store'])->name('staff.import');

    Route::get('dues/record', [DuesPaymentController::class, 'create'])->name('dues.record');
    Route::get('dues/search', [DuesPaymentController::class, 'search'])->name('dues.search');
    Route::get('dues/staff/{staff}/summary', [DuesPaymentController::class, 'summary'])->name('dues.staff-summary');
    Route::post('dues', [DuesPaymentController::class, 'store'])->name('dues.store');
    Route::get('dues/transactions', [DuesPaymentController::class, 'index'])->name('dues.index');
    Route::post('dues/{duesPayment}/deletion-request', [DuesPaymentController::class, 'requestDeletion'])->name('dues.deletion-request');
    Route::post('dues/deletion-requests/{deletionRequest}/approve', [DuesPaymentController::class, 'approveDeletion'])->name('dues.deletion-requests.approve');
    Route::post('dues/deletion-requests/{deletionRequest}/reject', [DuesPaymentController::class, 'rejectDeletion'])->name('dues.deletion-requests.reject');
    Route::get('dues/bulk', [BulkDuesPaymentController::class, 'create'])->name('dues.bulk');
    Route::post('dues/bulk', [BulkDuesPaymentController::class, 'store'])->name('dues.bulk.store');

    Route::resource('benefit-types', BenefitTypeController::class)
        ->except(['show', 'destroy'])
        ->parameters(['benefit-types' => 'benefitType']);
    Route::resource('benefits', BenefitController::class)->except(['show', 'destroy']);
    Route::post('benefits/{benefit}/mark-paid', [BenefitController::class, 'markPaid'])->name('benefits.mark-paid');

    Route::get('benefit-requests', [BenefitRequestController::class, 'adminIndex'])->name('benefit-requests.index');
    Route::get('benefit-requests/{benefitRequest}', [BenefitRequestController::class, 'adminShow'])->name('benefit-requests.show');
    Route::post('benefit-requests/{benefitRequest}/review', [BenefitRequestController::class, 'review'])->name('benefit-requests.review');

    Route::get('reports/dues', [ReportController::class, 'dues'])->name('reports.dues');
    Route::get('reports/benefits', [ReportController::class, 'benefits'])->name('reports.benefits');
    Route::get('reports/staff/{staff}/statement', [ReportController::class, 'statement'])->name('reports.statement');

    Route::get('exports/annual-dues-chart', [ExportController::class, 'duesChart'])->name('exports.annual-dues-chart');
    Route::get('exports/dues-transactions', [ExportController::class, 'transactions'])->name('exports.dues-transactions');
    Route::get('exports/staff/{staff}/statement', [ExportController::class, 'staffStatement'])->name('exports.staff-statement');
    Route::get('exports/benefits', [ExportController::class, 'benefits'])->name('exports.benefits');
    Route::get('exports/pending-benefits', [ExportController::class, 'benefits'])->defaults('status', 'pending')->name('exports.pending-benefits');
    Route::get('exports/financial-summary', [ExportController::class, 'financialSummary'])->name('exports.financial-summary');

    Route::get('import', [ImportController::class, 'index'])->name('import.index');
    Route::post('import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::get('import/{importBatch}', [ImportController::class, 'show'])->name('import.show');
    Route::post('import/{importBatch}/commit', [ImportController::class, 'commit'])->name('import.commit');
    Route::post('import/{importBatch}/rows/{row}/resolve', [ImportController::class, 'resolve'])->name('import.rows.resolve');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/mode', [SettingController::class, 'updateMode'])->name('settings.mode');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
});

Route::middleware(['auth', 'role:Staff Member', 'password.changed'])->prefix('staff-portal')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/dues', [StaffPortalController::class, 'dues'])->name('dues');
    Route::get('/benefits', [StaffPortalController::class, 'benefits'])->name('benefits');
    Route::get('/profile', [StaffPortalController::class, 'profile'])->name('profile');
    Route::put('/profile', [StaffPortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('/change-password', [StaffPortalController::class, 'changePassword'])->name('password.edit');
    Route::post('/change-password', [StaffPortalController::class, 'updatePassword'])->name('password.update');
    Route::get('/benefit-requests', [BenefitRequestController::class, 'staffIndex'])->name('requests.index');
    Route::get('/benefit-requests/create', [BenefitRequestController::class, 'create'])->name('requests.create');
    Route::post('/benefit-requests', [BenefitRequestController::class, 'store'])->name('requests.store');
    Route::get('/benefit-requests/{benefitRequest}', [BenefitRequestController::class, 'staffShow'])->name('requests.show');
});

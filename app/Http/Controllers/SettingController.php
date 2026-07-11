<?php

namespace App\Http\Controllers;

use App\Models\DuesRate;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => Setting::query()->orderBy('key')->get()->keyBy('key'),
            'duesRates' => DuesRate::query()->latest('effective_from')->get(),
        ]);
    }

    public function update(Request $request, AuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'association_name' => ['required', 'string', 'max:255'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'application_name' => ['required', 'string', 'max:255'],
            'default_currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'financial_year_start_month' => ['required', 'integer', 'between:1,12'],
            'session_timeout_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'system_mode' => ['required', 'in:production,debug'],
            'dues_name' => ['nullable', 'string', 'max:150'],
            'dues_amount' => ['nullable', 'numeric', 'min:0'],
            'dues_effective_from' => ['nullable', 'date'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
        ]);

        $uploaded = [];
        foreach (['logo', 'favicon'] as $field) {
            if ($request->hasFile($field)) {
                $uploaded[$field.'_path'] = $request->file($field)->store('branding', 'public');
            }
        }

        DB::transaction(function () use ($validated, $request, $uploaded) {
            foreach (['association_name', 'institution_name', 'application_name', 'default_currency', 'currency_symbol', 'financial_year_start_month', 'session_timeout_minutes', 'system_mode'] as $key) {
                Setting::query()->updateOrCreate(['key' => $key], [
                    'value' => $validated[$key],
                    'type' => in_array($key, ['financial_year_start_month', 'session_timeout_minutes'], true) ? 'integer' : 'string',
                ]);
            }

            foreach ($uploaded as $key => $path) {
                $oldPath = Setting::value($key);
                Setting::query()->updateOrCreate(['key' => $key], ['value' => $path, 'type' => 'string']);
                if ($oldPath && str_starts_with($oldPath, 'branding/') && $oldPath !== $path) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            if ($request->filled('dues_amount') && $request->filled('dues_effective_from')) {
                DuesRate::query()->create([
                    'name' => $request->input('dues_name') ?: 'Monthly dues',
                    'amount' => $request->input('dues_amount'),
                    'effective_from' => $request->input('dues_effective_from'),
                    'is_active' => true,
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        $audit->log('settings_updated', null, [], array_merge(
            array_diff_key($validated, ['logo' => true, 'favicon' => true]),
            $uploaded,
        ));

        return back()->with('success', 'Settings updated.');
    }

    public function updateMode(Request $request, AuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'system_mode' => ['required', 'in:production,debug'],
            'password' => ['required', 'current_password'],
        ]);
        $old = Setting::value('system_mode', 'production');
        Setting::query()->updateOrCreate(['key' => 'system_mode'], ['value' => $validated['system_mode'], 'type' => 'string']);
        $audit->log('system_mode_changed', null, ['system_mode' => $old], ['system_mode' => $validated['system_mode']], $request);

        return back()->with('success', 'System mode changed to '.ucfirst($validated['system_mode']).'.');
    }
}

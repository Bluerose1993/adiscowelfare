<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.administrators.index', [
            'administrators' => User::role('Administrator')->with('permissions')->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.administrators.form', [
            'administrator' => new User(),
            'permissions' => $this->permissions(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $validated = $this->validateAdmin($request);
        $administrator = DB::transaction(function () use ($validated, $request, $audit) {
            $administrator = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?: null,
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
                'must_change_password' => false,
            ]);
            $administrator->assignRole('Administrator');
            $administrator->syncPermissions($validated['permissions'] ?? []);
            $audit->log('administrator_created', $administrator, [], $administrator->load('permissions')->toArray(), $request);

            return $administrator;
        });

        return redirect()->route('admin.administrators.edit', $administrator)->with('success', 'Administrator account created.');
    }

    public function edit(User $administrator): View
    {
        $this->ensureAdministrator($administrator);

        return view('admin.administrators.form', [
            'administrator' => $administrator,
            'permissions' => $this->permissions(),
            'selectedPermissions' => $administrator->getDirectPermissions()->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, User $administrator, AuditService $audit): RedirectResponse
    {
        $this->ensureAdministrator($administrator);
        $validated = $this->validateAdmin($request, $administrator);
        if ($administrator->is($request->user()) && ! in_array('manage administrators', $validated['permissions'] ?? [], true)) {
            return back()->withInput()->withErrors(['permissions' => 'You cannot remove your own administrator-management permission.']);
        }

        DB::transaction(function () use ($validated, $request, $administrator, $audit) {
            $old = $administrator->load('permissions')->toArray();
            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'] ?: null,
                'username' => $validated['username'],
                'status' => $validated['status'],
            ];
            if (! empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }
            $administrator->update($data);
            $administrator->syncPermissions($validated['permissions'] ?? []);
            $audit->log('administrator_updated', $administrator, $old, $administrator->fresh()->load('permissions')->toArray(), $request);
        });

        return back()->with('success', 'Administrator account and system access updated.');
    }

    public function resetPassword(Request $request, User $administrator, AuditService $audit): RedirectResponse
    {
        $this->ensureAdministrator($administrator);
        $validated = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $administrator->update(['password' => Hash::make($validated['password'])]);
        $audit->log('administrator_password_reset', $administrator, [], [], $request);

        return back()->with('success', 'Administrator password reset.');
    }

    public function toggleStatus(Request $request, User $administrator, AuditService $audit): RedirectResponse
    {
        $this->ensureAdministrator($administrator);
        if ($administrator->is($request->user())) {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }
        $old = $administrator->status;
        $administrator->update(['status' => $old === 'active' ? 'inactive' : 'active']);
        $audit->log('administrator_status_changed', $administrator, ['status' => $old], ['status' => $administrator->status], $request);

        return back()->with('success', 'Administrator status updated.');
    }

    private function validateAdmin(Request $request, ?User $administrator = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($administrator?->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($administrator?->id)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$administrator ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);
    }

    private function ensureAdministrator(User $administrator): void
    {
        abort_unless($administrator->hasRole('Administrator'), 404);
    }

    private function permissions()
    {
        return Permission::query()->where('guard_name', 'web')
            ->whereNotIn('name', ['submit benefit requests', 'view own records'])
            ->orderBy('name')->get();
    }
}

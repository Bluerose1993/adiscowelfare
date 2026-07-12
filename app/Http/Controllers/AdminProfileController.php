<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(Request $request): View
    {
        if ($request->user()->staff) {
            return view('staff.profile', [
                'staff' => $request->user()->staff,
                'profileUpdateRoute' => route('admin.profile.update'),
            ]);
        }

        return view('admin.profile', ['administrator' => $request->user()]);
    }

    public function update(Request $request, AuditService $audit): RedirectResponse
    {
        $user = $request->user();
        $staff = $user->staff;
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];
        if ($staff) {
            $rules += [
                'phone' => ['nullable', 'string', 'max:30'], 'gender' => ['nullable', 'string', 'max:50'],
                'department' => ['nullable', 'string', 'max:150'], 'position' => ['nullable', 'string', 'max:150'],
                'employment_status' => ['nullable', 'string', 'max:100'], 'date_joined' => ['nullable', 'date'],
                'association_joined_at' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }
        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $user, $staff, $validated, $audit) {
            $old = $staff?->toArray() ?? $user->only(['name', 'email']);
            if ($staff) $staff->update($validated);
            $user->update(['name' => $validated['full_name'], 'email' => $validated['email'] ?: null]);
            $audit->log('administrator_profile_updated', $staff ?: $user, $old, $staff?->fresh()->toArray() ?? $user->fresh()->only(['name', 'email']), $request);
        });

        return back()->with('success', 'Your profile has been updated.');
    }

    public function editPassword(): View
    {
        return view('staff.change-password', ['passwordUpdateRoute' => route('admin.password.update')]);
    }

    public function updatePassword(Request $request, AuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();
        $audit->log('administrator_password_changed', $request->user(), [], [], $request);

        return back()->with('success', 'Your password has been changed successfully.');
    }
}

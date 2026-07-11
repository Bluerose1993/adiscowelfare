<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view dashboard',
            'manage staff',
            'manage dues',
            'manage benefits',
            'review benefit requests',
            'view reports',
            'manage settings',
            'view audit logs',
            'manage administrators',
            'submit benefit requests',
            'view own records',
        ];

        $permissionModels = collect($permissions)
            ->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('Administrator', 'web');
        $staff = Role::findOrCreate('Staff Member', 'web');

        $admin->syncPermissions([]);
        $staff->syncPermissions($permissionModels->whereIn('name', ['submit benefit requests', 'view own records']));

        \App\Models\User::role('Administrator')->get()->each(
            fn (\App\Models\User $user) => $user->syncPermissions($permissionModels)
        );
    }
}

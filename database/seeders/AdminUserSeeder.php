<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => null,
                'password' => Hash::make('ChangeMe123!'),
                'status' => 'active',
                'must_change_password' => true,
            ]
        );

        $admin->assignRole('Administrator');
        $admin->syncPermissions(Permission::query()->where('guard_name', 'web')->pluck('name'));
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'Administrator')->where('guard_name', 'web')->first();
        if (! $role) {
            return;
        }

        DB::table('permissions')->insertOrIgnore([
            'name' => 'manage administrators',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionIds = DB::table('permissions')->pluck('id');
        $adminUserIds = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', \App\Models\User::class)
            ->pluck('model_id');

        foreach ($adminUserIds as $userId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'model_type' => \App\Models\User::class,
                    'model_id' => $userId,
                ]);
            }
        }

        DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'Administrator')->where('guard_name', 'web')->first();
        if (! $role) {
            return;
        }

        DB::table('permissions')->pluck('id')->each(fn ($permissionId) =>
            DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $role->id])
        );
    }
};

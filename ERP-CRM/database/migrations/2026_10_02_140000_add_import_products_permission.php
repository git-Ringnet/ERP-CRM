<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Add import_products permission
        $permission = [
            'name' => 'Import Excel Sản phẩm',
            'slug' => 'import_products',
            'description' => 'Quyền upload file Excel import danh sách sản phẩm',
            'module' => 'products',
            'action' => 'import',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('permissions')->updateOrInsert(
            ['slug' => 'import_products'],
            $permission
        );

        $permId = DB::table('permissions')->where('slug', 'import_products')->value('id');

        if ($permId) {
            // Assign to super_admin, admin, warehouse_manager, and any role that has create_products
            $roleIds = DB::table('roles')
                ->whereIn('slug', ['super_admin', 'admin', 'warehouse_manager'])
                ->pluck('id')
                ->toArray();

            $rolesWithCreate = DB::table('role_permissions')
                ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where('permissions.slug', 'create_products')
                ->pluck('role_permissions.role_id')
                ->toArray();

            $targetRoleIds = array_unique(array_merge($roleIds, $rolesWithCreate));

            foreach ($targetRoleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permId],
                    ['created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $permId = DB::table('permissions')->where('slug', 'import_products')->value('id');
        if ($permId) {
            DB::table('role_permissions')->where('permission_id', $permId)->delete();
            DB::table('user_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};

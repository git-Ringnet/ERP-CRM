<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'Xem tổng doanh số',
                'slug' => 'view_sales_revenues',
                'description' => 'Xem bảng theo dõi tổng doanh số & thanh toán',
                'module' => 'sales_revenues',
                'action' => 'view',
            ],
            [
                'name' => 'Tạo tổng doanh số',
                'slug' => 'create_sales_revenues',
                'description' => 'Tạo mới dòng theo dõi doanh số',
                'module' => 'sales_revenues',
                'action' => 'create',
            ],
            [
                'name' => 'Sửa tổng doanh số',
                'slug' => 'edit_sales_revenues',
                'description' => 'Chỉnh sửa dòng theo dõi doanh số',
                'module' => 'sales_revenues',
                'action' => 'edit',
            ],
            [
                'name' => 'Xóa tổng doanh số',
                'slug' => 'delete_sales_revenues',
                'description' => 'Xóa dòng theo dõi doanh số',
                'module' => 'sales_revenues',
                'action' => 'delete',
            ],
        ];

        $now = now();
        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $perm['slug']],
                array_merge($perm, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        // Auto-assign all new permissions to admin role (role_id = 1)
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id')
            ?? DB::table('roles')->orderBy('id')->value('id');

        if ($adminRoleId) {
            $permissionIds = DB::table('permissions')
                ->where('module', 'sales_revenues')
                ->pluck('id');

            foreach ($permissionIds as $permId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $adminRoleId, 'permission_id' => $permId],
                    ['created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('module', 'sales_revenues')
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->where('module', 'sales_revenues')->delete();
    }
};

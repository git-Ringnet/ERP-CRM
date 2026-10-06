<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo permission view_all_exports
        $viewAllPerm = Permission::firstOrCreate(
            ['slug' => 'view_all_exports'],
            [
                'name' => 'Xem Tất cả Phiếu xuất kho',
                'description' => 'Quyền xem tất cả phiếu xuất kho trong hệ thống',
                'module' => 'exports',
                'action' => 'view',
            ]
        );

        // 2. Tạo permission view_own_exports
        $viewOwnPerm = Permission::firstOrCreate(
            ['slug' => 'view_own_exports'],
            [
                'name' => 'Xem Phiếu xuất kho Của mình',
                'description' => 'Quyền chỉ xem phiếu xuất kho của bản thân hoặc đơn hàng của mình',
                'module' => 'exports',
                'action' => 'view',
            ]
        );

        // Gán view_all_exports cho các vai trò quản lý / kho / admin
        $viewAllRoleSlugs = [
            'super_admin',
            'admin',
            'director',
            'warehouse_manager',
            'warehouse_staff',
            'purchase_manager',
            'accountant',
            'order_management',
            'legal_team',
            'sales_manager',
        ];

        $rolesAll = Role::whereIn('slug', $viewAllRoleSlugs)->get();
        foreach ($rolesAll as $role) {
            if (!$role->permissions()->where('permissions.id', $viewAllPerm->id)->exists()) {
                $role->permissions()->attach($viewAllPerm->id);
            }
        }

        // Gán view_own_exports cho sales_staff và sales_manager
        $viewOwnRoleSlugs = ['sales_staff', 'sales_manager'];
        $rolesOwn = Role::whereIn('slug', $viewOwnRoleSlugs)->get();
        foreach ($rolesOwn as $role) {
            if (!$role->permissions()->where('permissions.id', $viewOwnPerm->id)->exists()) {
                $role->permissions()->attach($viewOwnPerm->id);
            }
        }

        // Đảm bảo sales_staff KHÔNG có view_all_exports
        $salesStaffRole = Role::where('slug', 'sales_staff')->first();
        if ($salesStaffRole) {
            $salesStaffRole->permissions()->detach($viewAllPerm->id);
        }
    }

    public function down(): void
    {
        $perms = Permission::whereIn('slug', ['view_all_exports', 'view_own_exports'])->get();
        foreach ($perms as $perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('user_permissions')->where('permission_id', $perm->id)->delete();
            $perm->delete();
        }
    }
};

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
        // 1. Tạo permission view_group_sales
        $viewGroupSales = Permission::firstOrCreate(
            ['slug' => 'view_group_sales'],
            [
                'name' => 'Xem Đơn hàng Theo Phân nhóm',
                'description' => 'Quyền xem đơn hàng bán của các nhân viên thuộc nhóm mình quản lý',
                'module' => 'sales',
                'action' => 'view',
            ]
        );

        // 2. Tạo permission view_group_quotations
        $viewGroupQuotations = Permission::firstOrCreate(
            ['slug' => 'view_group_quotations'],
            [
                'name' => 'Xem Báo giá Theo Phân nhóm',
                'description' => 'Quyền xem báo giá của các nhân viên thuộc nhóm mình quản lý',
                'module' => 'quotations',
                'action' => 'view',
            ]
        );

        // 3. Gán quyền xem theo nhóm cho vai trò Sales Manager
        $salesManagerRole = Role::where('slug', 'sales_manager')->first();
        if ($salesManagerRole) {
            if (!$salesManagerRole->permissions()->where('permissions.id', $viewGroupSales->id)->exists()) {
                $salesManagerRole->permissions()->attach($viewGroupSales->id);
            }
            if (!$salesManagerRole->permissions()->where('permissions.id', $viewGroupQuotations->id)->exists()) {
                $salesManagerRole->permissions()->attach($viewGroupQuotations->id);
            }

            // Mặc định Sales Manager xem theo phân nhóm, không mặc định xem hết toàn công ty.
            // Nếu muốn Manager xem hết, Admin chỉ cần tick chọn "Xem Tất cả Đơn hàng" (view_all_sales) và "Xem Tất cả Báo giá" (view_all_quotations) trong Ma trận quyền.
            $viewAllSales = Permission::where('slug', 'view_all_sales')->first();
            $viewAllQuotations = Permission::where('slug', 'view_all_quotations')->first();
            if ($viewAllSales) {
                $salesManagerRole->permissions()->detach($viewAllSales->id);
            }
            if ($viewAllQuotations) {
                $salesManagerRole->permissions()->detach($viewAllQuotations->id);
            }
        }
    }

    public function down(): void
    {
        $perms = Permission::whereIn('slug', ['view_group_sales', 'view_group_quotations'])->get();
        foreach ($perms as $perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('user_permissions')->where('permission_id', $perm->id)->delete();
            $perm->delete();
        }
    }
};

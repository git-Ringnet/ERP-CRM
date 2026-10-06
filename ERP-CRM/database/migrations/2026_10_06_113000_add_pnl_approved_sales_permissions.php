<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tạo permission edit_approved_pnl_sales
        $editApprovedPnlSales = Permission::firstOrCreate(
            ['slug' => 'edit_approved_pnl_sales'],
            [
                'name' => 'Sửa đơn hàng đã duyệt P&L',
                'description' => 'Cho phép sửa đơn hàng bán sau khi P&L đã được duyệt',
                'module' => 'sales',
                'action' => 'edit',
            ]
        );

        // 2. Tạo permission delete_approved_pnl_sales
        $deleteApprovedPnlSales = Permission::firstOrCreate(
            ['slug' => 'delete_approved_pnl_sales'],
            [
                'name' => 'Xóa đơn hàng đã duyệt P&L',
                'description' => 'Cho phép xóa đơn hàng bán sau khi P&L đã được duyệt',
                'module' => 'sales',
                'action' => 'delete',
            ]
        );

        // 3. Gán mặc định cho vai trò Admin
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching([
                $editApprovedPnlSales->id,
                $deleteApprovedPnlSales->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perms = Permission::whereIn('slug', ['edit_approved_pnl_sales', 'delete_approved_pnl_sales'])->get();
        foreach ($perms as $perm) {
            DB::table('role_permissions')->where('permission_id', $perm->id)->delete();
            DB::table('user_permissions')->where('permission_id', $perm->id)->delete();
            $perm->delete();
        }
    }
};

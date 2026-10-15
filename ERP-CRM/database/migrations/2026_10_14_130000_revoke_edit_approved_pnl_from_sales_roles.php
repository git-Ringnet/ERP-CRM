<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * PNL 6: P&L Chờ duyệt / Đã duyệt thì khóa chi tiết sản phẩm. Sales không được tự sửa
     * đơn đã duyệt P&L; muốn bổ sung phải gửi yêu cầu BOD mở lại (PNL 7).
     */
    private const ROLES = ['sales_staff', 'sales_manager'];

    public function up(): void
    {
        $permission = Permission::where('slug', 'edit_approved_pnl_sales')->first();
        if (!$permission) {
            return;
        }

        Role::whereIn('slug', self::ROLES)->get()
            ->each(fn ($role) => $role->permissions()->detach($permission->id));
    }

    public function down(): void
    {
        $permission = Permission::where('slug', 'edit_approved_pnl_sales')->first();
        if (!$permission) {
            return;
        }

        Role::whereIn('slug', self::ROLES)->get()
            ->each(fn ($role) => $role->permissions()->syncWithoutDetaching([$permission->id]));
    }
};

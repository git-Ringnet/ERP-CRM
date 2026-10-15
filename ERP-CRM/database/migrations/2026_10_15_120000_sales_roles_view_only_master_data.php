<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Chung 4: Sales chỉ XEM Khách hàng / Nhà cung cấp / Sản phẩm; Admin quản trị toàn quyền.
     * (Tạo nhanh khách hàng ngay trên form Đơn hàng bán / Cơ hội vẫn được phép – xử lý ở CustomerController.)
     */
    private const ROLES = ['sales_staff', 'sales_manager'];

    private const REVOKE = [
        'create_customers', 'edit_customers', 'delete_customers',
        'create_suppliers', 'edit_suppliers', 'delete_suppliers',
        'create_products', 'edit_products', 'delete_products', 'import_products',
    ];

    private const GRANT = ['view_customers', 'view_suppliers', 'view_products'];

    public function up(): void
    {
        $revokeIds = Permission::whereIn('slug', self::REVOKE)->pluck('id');
        $grantIds = Permission::whereIn('slug', self::GRANT)->pluck('id');

        Role::whereIn('slug', self::ROLES)->get()->each(function (Role $role) use ($revokeIds, $grantIds) {
            $role->permissions()->detach($revokeIds);
            $role->permissions()->syncWithoutDetaching($grantIds);
        });
    }

    public function down(): void
    {
        // Khôi phục quyền như trước khi chạy migration
        $before = [
            'sales_staff' => ['create_customers', 'edit_customers'],
            'sales_manager' => ['create_customers', 'edit_customers', 'delete_customers', 'create_products', 'edit_products', 'delete_products', 'import_products'],
        ];
        foreach ($before as $slug => $perms) {
            $role = Role::where('slug', $slug)->first();
            $role?->permissions()->syncWithoutDetaching(Permission::whereIn('slug', $perms)->pluck('id'));
            $role?->permissions()->detach(Permission::where('slug', 'view_suppliers')->pluck('id'));
        }
    }
};

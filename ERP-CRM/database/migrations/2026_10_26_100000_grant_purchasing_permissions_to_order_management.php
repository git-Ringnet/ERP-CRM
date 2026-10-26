<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Order Management (chị Bích) duyệt yêu cầu đặt hàng và quản lý PO Team:
     * cấp thêm quyền mua hàng như PO Manager (duyệt PR, gom đơn, NCC, bảng giá NCC, báo cáo mua hàng).
     * Chỉ cộng thêm, không xóa quyền đang có (RoleSeeder truncate toàn bộ nên không chạy lại trên server).
     */
    private array $modules = [
        'suppliers', 'supplier_quotations', 'supplier_price_lists', 'purchase_reports', 'cost_formulas',
        'pr_approvals', 'needs_ordering',
    ];

    private array $slugs = ['view_inventory'];

    public function up(): void
    {
        $roleId = DB::table('roles')->where('slug', 'order_management')->value('id');
        if (!$roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('module', $this->modules)
            ->orWhereIn('slug', $this->slugs)
            ->pluck('id');

        $existing = DB::table('role_permissions')->where('role_id', $roleId)->pluck('permission_id')->all();

        $rows = $permissionIds->diff($existing)->map(fn ($id) => [
            'role_id' => $roleId,
            'permission_id' => $id,
            'created_at' => now(),
        ])->values()->all();

        if (!empty($rows)) {
            DB::table('role_permissions')->insert($rows);
        }

        $this->forgetPermissionCache($roleId);
    }

    /**
     * Quyền của từng user được cache 1 giờ (PermissionService) — xóa để quyền mới có hiệu lực ngay.
     */
    private function forgetPermissionCache(int $roleId): void
    {
        $userIds = DB::table('user_roles')->where('role_id', $roleId)->pluck('user_id');
        foreach ($userIds as $userId) {
            try {
                Cache::forget(sprintf('user_permissions:%d', $userId));
            } catch (\Throwable $e) {
                // Cache không khả dụng: quyền sẽ cập nhật khi cache hết hạn
            }
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'order_management')->value('id');
        if (!$roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('module', $this->modules)
            ->orWhereIn('slug', $this->slugs)
            ->pluck('id');

        DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        $this->forgetPermissionCache($roleId);
    }
};

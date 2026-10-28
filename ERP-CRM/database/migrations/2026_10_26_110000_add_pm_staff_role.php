<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PM Team (tiếp nhận ĐKDA Non-FTN) chưa có phòng ban/role nào trong hệ thống:
     * thêm role pm_staff để gán cho nhân sự PM, thay cho việc đặt phòng ban 'PM'.
     * Không chạy lại RoleSeeder trên server (seeder truncate toàn bộ quyền).
     */
    private array $permissionSlugs = [
        'view_projects', 'create_projects', 'edit_projects', 'export_projects', 'view_dashboard',
    ];

    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['slug' => 'pm_staff'],
            [
                'name' => 'PM Team',
                'description' => 'Tiếp nhận, xử lý đăng ký dự án Non-FTN (các hãng ngoài Fortinet)',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $roleId = DB::table('roles')->where('slug', 'pm_staff')->value('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', $this->permissionSlugs)->pluck('id');
        $existing = DB::table('role_permissions')->where('role_id', $roleId)->pluck('permission_id')->all();

        $rows = $permissionIds->diff($existing)->map(fn ($id) => [
            'role_id' => $roleId,
            'permission_id' => $id,
            'created_at' => now(),
        ])->values()->all();

        if (!empty($rows)) {
            DB::table('role_permissions')->insert($rows);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('slug', 'pm_staff')->value('id');
        if (!$roleId) {
            return;
        }

        DB::table('role_permissions')->where('role_id', $roleId)->delete();
        DB::table('user_roles')->where('role_id', $roleId)->delete();
        DB::table('roles')->where('id', $roleId)->delete();
    }
};

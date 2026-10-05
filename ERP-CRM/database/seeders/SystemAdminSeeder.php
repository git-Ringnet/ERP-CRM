<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SystemAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Tạo tài khoản quản trị viên dự phòng ẩn, bảo vệ khỏi việc bị chỉnh sửa hoặc xóa nhầm.
     */
    public function run(): void
    {
        $email = 'system_admin@mail.com';
        $password = 'password@123';

        // 1. Tìm hoặc tạo tài khoản Quản trị viên hệ thống
        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'System Administrator',
                'password' => Hash::make($password),
                'employee_code' => null, // Không cấp mã nhân viên để không xuất hiện trong danh sách nhân viên
                'phone' => '0900000000',
                'department' => 'IT System',
                'position' => 'Super Administrator',
                'status' => 'active',
                'is_locked' => false,
                'is_hidden' => true, // Đánh dấu tài khoản ẩn bảo vệ đặc biệt
                'join_date' => now(),
            ]
        );

        // 2. Gán vai trò Super Admin với toàn quyền hệ thống
        $superAdminRole = Role::where('slug', 'super_admin')->first();

        if ($superAdminRole) {
            if (!$admin->roles->contains($superAdminRole->id)) {
                $admin->roles()->attach($superAdminRole->id, [
                    'assigned_by' => $admin->id,
                    'assigned_at' => now(),
                ]);
            }
            $this->command->info("✓ Đã cấp quyền Super Admin cho tài khoản: {$email}");
        } else {
            $this->command->warn("! Không tìm thấy vai trò 'super_admin'. Vui lòng chạy RoleSeeder trước.");
        }

        $this->command->info("✓ Tạo/Cập nhật tài khoản quản trị viên ẩn thành công:");
        $this->command->line("   - Email:    {$email}");
        $this->command->line("   - Password: {$password}");
        $this->command->line("   - Trạng thái ẩn: true (Không hiển thị trong danh sách nhân viên)");
    }
}

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SỬA QUYỀN CHO USER ===\n\n";

// Tìm user
$user = DB::table('users')->where('email', 'hobertkhanh@gmail.com')->first();
if (!$user) {
    echo "❌ User không tồn tại\n";
    exit;
}

echo "User: {$user->name} ({$user->email})\n\n";

// Lấy vai trò hiện tại
$currentRoles = DB::table('roles')
    ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
    ->where('user_roles.user_id', $user->id)
    ->select('roles.id', 'roles.name', 'roles.slug')
    ->get();

echo "Vai trò hiện tại:\n";
foreach ($currentRoles as $role) {
    echo "  - {$role->name} ({$role->slug})\n";
}

// Xóa cache permissions của user này
echo "\n🔄 Xóa cache permissions...\n";
try {
    $cacheKey = "user_permissions:{$user->id}";
    \Illuminate\Support\Facades\Cache::forget($cacheKey);
    echo "✅ Đã xóa cache: {$cacheKey}\n";
} catch (\Exception $e) {
    echo "⚠️ Không thể xóa cache (có thể Redis chưa chạy): {$e->getMessage()}\n";
}

// Kiểm tra quyền work_schedules sau khi cập nhật
echo "\n=== KIỂM TRA QUYỀN WORK_SCHEDULES ===\n";
$workSchedulePerms = DB::table('permissions')
    ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
    ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
    ->where('user_roles.user_id', $user->id)
    ->where('permissions.module', 'work_schedules')
    ->select('permissions.name', 'permissions.slug', 'permissions.action')
    ->get();

if ($workSchedulePerms->isEmpty()) {
    echo "❌ KHÔNG CÓ QUYỀN WORK_SCHEDULES\n";
    echo "\n🔧 Đang thêm quyền trực tiếp...\n";
    
    // Thêm quyền view_work_schedules trực tiếp cho user
    $viewWorkSchedulePerm = DB::table('permissions')
        ->where('slug', 'view_work_schedules')
        ->first();
    
    if ($viewWorkSchedulePerm) {
        DB::table('user_permissions')->insertOrIgnore([
            'user_id' => $user->id,
            'permission_id' => $viewWorkSchedulePerm->id,
            'assigned_by' => 1, // Admin
            'created_at' => now(),
        ]);
        echo "✅ Đã thêm quyền: view_work_schedules\n";
    }
} else {
    echo "✅ Có quyền work_schedules:\n";
    foreach ($workSchedulePerms as $perm) {
        echo "  - {$perm->name} ({$perm->action})\n";
    }
}

echo "\n=== HƯỚNG DẪN ===\n";
echo "1. User cần LOGOUT và LOGIN lại để cache được làm mới\n";
echo "2. Hoặc chạy: php artisan cache:clear\n";
echo "3. Nếu vẫn lỗi, kiểm tra session: php artisan session:flush\n";

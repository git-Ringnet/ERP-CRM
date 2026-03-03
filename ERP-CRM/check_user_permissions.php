<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Tìm user với email
$user = DB::table('users')->where('email', 'hobertkhanh@gmail.com')->first();
if (!$user) {
    echo "User không tồn tại\n";
    exit;
}

echo "=== THÔNG TIN USER ===\n";
echo "ID: {$user->id}\n";
echo "Name: {$user->name}\n";
echo "Email: {$user->email}\n\n";

// Lấy vai trò
$roles = DB::table('roles')
    ->join('user_roles', 'roles.id', '=', 'user_roles.role_id')
    ->where('user_roles.user_id', $user->id)
    ->select('roles.name', 'roles.slug')
    ->get();

echo "=== VAI TRÒ ===\n";
if ($roles->isEmpty()) {
    echo "❌ CHƯA CÓ VAI TRÒ NÀO\n";
} else {
    foreach ($roles as $role) {
        echo "- {$role->name} ({$role->slug})\n";
        
        // Đếm số quyền của vai trò này
        $permCount = DB::table('role_permissions')
            ->where('role_id', DB::table('roles')->where('slug', $role->slug)->value('id'))
            ->count();
        echo "  Số quyền: {$permCount}\n";
    }
}

// Kiểm tra quyền approve
echo "\n=== QUYỀN APPROVE ===\n";
$approvePerms = DB::table('permissions')
    ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
    ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
    ->where('user_roles.user_id', $user->id)
    ->where('permissions.slug', 'like', 'approve_%')
    ->select('permissions.name', 'permissions.slug')
    ->get();

if ($approvePerms->isEmpty()) {
    echo "❌ KHÔNG CÓ QUYỀN APPROVE NÀO\n";
    echo "\nĐể có quyền duyệt phiếu nhập/xuất kho, cần gán vai trò:\n";
    echo "- Quản lý Kho (warehouse_manager)\n";
    echo "- Hoặc Giám đốc (director)\n";
} else {
    foreach ($approvePerms as $perm) {
        echo "✅ {$perm->name} ({$perm->slug})\n";
    }
}

// Kiểm tra quyền imports
echo "\n=== QUYỀN NHẬP KHO ===\n";
$importPerms = DB::table('permissions')
    ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
    ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
    ->where('user_roles.user_id', $user->id)
    ->where('permissions.module', 'imports')
    ->select('permissions.name', 'permissions.slug', 'permissions.action')
    ->get();

foreach ($importPerms as $perm) {
    echo "✅ {$perm->name} ({$perm->action})\n";
}

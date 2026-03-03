<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUYỀN WORK_SCHEDULES (LỊCH LÀM VIỆC) ===\n\n";

// Lấy tất cả permissions của work_schedules
$permissions = DB::table('permissions')
    ->where('module', 'work_schedules')
    ->select('id', 'name', 'slug', 'action')
    ->get();

echo "Các quyền có sẵn:\n";
foreach ($permissions as $perm) {
    echo "  - {$perm->name} ({$perm->slug})\n";
}

echo "\n=== VAI TRÒ CÓ QUYỀN WORK_SCHEDULES ===\n\n";

// Kiểm tra từng vai trò
$roles = DB::table('roles')->get();

foreach ($roles as $role) {
    $workSchedulePerms = DB::table('permissions')
        ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->where('role_permissions.role_id', $role->id)
        ->where('permissions.module', 'work_schedules')
        ->select('permissions.name', 'permissions.action')
        ->get();
    
    if ($workSchedulePerms->isNotEmpty()) {
        echo "✅ {$role->name} ({$role->slug}):\n";
        foreach ($workSchedulePerms as $perm) {
            echo "   - {$perm->action}: {$perm->name}\n";
        }
        echo "\n";
    } else {
        echo "❌ {$role->name} ({$role->slug}): KHÔNG CÓ QUYỀN\n\n";
    }
}

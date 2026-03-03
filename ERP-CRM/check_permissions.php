<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$roles = DB::table('roles')->get();

echo "=== KIỂM TRA PHÂN QUYỀN ===\n\n";

foreach ($roles as $role) {
    $count = DB::table('role_permissions')->where('role_id', $role->id)->count();
    echo sprintf("%-30s (%s): %d quyền\n", $role->name, $role->slug, $count);
}

echo "\n";
echo "Tổng số permissions trong hệ thống: " . DB::table('permissions')->count() . "\n";
echo "\n";

// Kiểm tra chi tiết Warehouse Manager
echo "=== CHI TIẾT WAREHOUSE MANAGER ===\n";
$warehouseManager = DB::table('roles')->where('slug', 'warehouse_manager')->first();
if ($warehouseManager) {
    $permissions = DB::table('permissions')
        ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->where('role_permissions.role_id', $warehouseManager->id)
        ->select('permissions.module', DB::raw('COUNT(*) as count'))
        ->groupBy('permissions.module')
        ->orderBy('count', 'desc')
        ->get();
    
    foreach ($permissions as $perm) {
        echo sprintf("  - %s: %d quyền\n", $perm->module, $perm->count);
    }
}

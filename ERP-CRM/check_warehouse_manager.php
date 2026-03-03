<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== WAREHOUSE MANAGER PERMISSIONS ===\n\n";

$warehouseManager = DB::table('roles')->where('slug', 'warehouse_manager')->first();

if (!$warehouseManager) {
    echo "Warehouse Manager role not found!\n";
    exit;
}

// Get all permissions for modules that warehouse manager should have
$expectedModules = ['warehouses', 'inventory', 'imports', 'exports', 'transfers', 'damaged_goods', 'products', 'excel_imports'];

echo "Expected modules: " . implode(', ', $expectedModules) . "\n\n";

foreach ($expectedModules as $module) {
    $totalPerms = DB::table('permissions')->where('module', $module)->count();
    $assignedPerms = DB::table('permissions')
        ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->where('role_permissions.role_id', $warehouseManager->id)
        ->where('permissions.module', $module)
        ->count();
    
    echo sprintf("%-25s: %d/%d quyền\n", $module, $assignedPerms, $totalPerms);
    
    if ($assignedPerms < $totalPerms) {
        // Show missing permissions
        $missing = DB::table('permissions')
            ->whereNotIn('id', function($query) use ($warehouseManager) {
                $query->select('permission_id')
                    ->from('role_permissions')
                    ->where('role_id', $warehouseManager->id);
            })
            ->where('module', $module)
            ->pluck('name')
            ->toArray();
        
        if (!empty($missing)) {
            echo "  Thiếu: " . implode(', ', $missing) . "\n";
        }
    }
}

// Check approve permissions
echo "\nApprove permissions:\n";
$approvePerms = ['approve_imports', 'approve_exports'];
foreach ($approvePerms as $slug) {
    $has = DB::table('permissions')
        ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
        ->where('role_permissions.role_id', $warehouseManager->id)
        ->where('permissions.slug', $slug)
        ->exists();
    
    echo sprintf("  %s: %s\n", $slug, $has ? 'CÓ' : 'THIẾU');
}

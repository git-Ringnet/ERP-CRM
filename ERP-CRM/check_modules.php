<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PERMISSIONS BY MODULE ===\n\n";

$modules = DB::table('permissions')
    ->select('module', DB::raw('COUNT(*) as count'))
    ->groupBy('module')
    ->orderBy('module')
    ->get();

foreach ($modules as $module) {
    echo sprintf("%-30s: %d quyền\n", $module->module, $module->count);
}

echo "\nTổng: " . DB::table('permissions')->count() . " quyền\n";

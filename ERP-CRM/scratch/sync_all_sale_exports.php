<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = App\Models\Sale::all();
$syncService = app(App\Services\SaleExportSyncService::class);
$count = 0;

foreach ($sales as $sale) {
    $syncService->syncExportSerialsFromSale($sale);
    $count++;
}

echo "Synced export serials for {$count} Sales successfully.\n";

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$po = App\Models\PurchaseOrder::where('code', '0005/2026/TH-Fortinet')->first();
if ($po) {
    app(App\Services\PurchaseImportSyncService::class)->syncImportSerialsFromPO($po);
    echo "Synced PO 0005/2026/TH-Fortinet successfully\n";
} else {
    echo "PO 0005/2026/TH-Fortinet not found\n";
}

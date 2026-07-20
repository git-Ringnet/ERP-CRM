<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$export = App\Models\Export::find(51);
$transactionService = app(App\Services\TransactionService::class);

try {
    $items = $export->items;
    foreach ($items as $item) {
        $valid = $transactionService->validateStock($item->product_id, $export->warehouse_id, $item->quantity);
        echo "Validate stock for product {$item->product_id} (qty {$item->quantity}): " . ($valid ? "VALID OK" : "INVALID FAIL") . "\n";
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

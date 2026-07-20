<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Export;
use App\Models\ExportItem;
use App\Models\ProductItem;
use App\Models\Sale;
use App\Models\Inventory;

$export = Export::where('code', 'EXP-20260719-0001')->first();
if (!$export) {
    echo "Export code EXP-20260719-0001 not found\n";
    exit;
}

echo "=== Export Info ===\n";
echo "Code: " . $export->code . "\n";
echo "Status: " . $export->status . "\n";
echo "Warehouse ID: " . $export->warehouse_id . " (" . ($export->warehouse->name ?? 'N/A') . ")\n";
echo "Reference Type: " . $export->reference_type . "\n";
echo "Reference ID: " . $export->reference_id . "\n";

$sale = null;
if ($export->reference_type === 'sale') {
    $sale = Sale::find($export->reference_id);
    if ($sale) {
        echo "Linked Sale Code: " . $sale->code . "\n";
        echo "Salesperson (User): " . ($sale->user->name ?? 'N/A') . " (ID: {$sale->user_id})\n";
        echo "Salesperson (Employee): " . ($sale->employee->name ?? 'N/A') . "\n";
    }
}

echo "\n=== Export Items ===\n";
foreach ($export->items as $item) {
    echo "Product ID: " . $item->product_id . " | Product Name: " . ($item->product->name ?? 'N/A') . "\n";
    echo "Quantity: " . $item->quantity . "\n";
    echo "Serial Numbers (JSON): " . $item->serial_number . "\n";
    
    // Check Inventory table stock
    $inv = Inventory::where('product_id', $item->product_id)
        ->where('warehouse_id', $export->warehouse_id)
        ->first();
    echo "Inventory DB Qty: " . ($inv ? $inv->quantity : 0) . "\n";

    // Check ProductItems list in this warehouse
    $productItems = ProductItem::where('product_id', $item->product_id)
        ->where('warehouse_id', $export->warehouse_id)
        ->get();
        
    echo "ProductItems in warehouse:\n";
    echo "  Total items in this warehouse: " . $productItems->count() . "\n";
    echo "  Breakdown by status:\n";
    foreach ($productItems->groupBy('status') as $status => $group) {
        echo "    * Status '{$status}': " . $group->count() . " items\n";
        if ($status === 'in_stock') {
            echo "      Detail of in_stock items:\n";
            foreach ($group as $pi) {
                echo "        - ID: {$pi->id} | SKU/Serial: {$pi->sku} | Borrower: '" . ($pi->borrower ?? '') . "' | Export ID: '" . ($pi->export_id ?? '') . "'\n";
            }
        }
    }
}

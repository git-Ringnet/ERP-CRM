<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$export = App\Models\Export::find(51);
$transactionService = app(App\Services\TransactionService::class);
$inventoryService = app(App\Services\InventoryService::class);

echo "Export ID: 51, Code: {$export->code}, Export WarehouseID: {$export->warehouse_id}\n";
$wh = App\Models\Warehouse::find($export->warehouse_id);
echo "Export Warehouse: " . ($wh ? "{$wh->code} - {$wh->name}" : "NULL") . "\n";

foreach ($export->items as $item) {
    echo "Item ID: {$item->id}, ProductID: {$item->product_id}, Product: {$item->product->code} ({$item->product->name}), Qty: {$item->quantity}\n";
    
    // Check Inventory table stock
    $inv = App\Models\Inventory::where('product_id', $item->product_id)
        ->where('warehouse_id', $export->warehouse_id)
        ->first();
    echo "  Inventory table stock (warehouse {$export->warehouse_id}): " . ($inv ? $inv->stock : 0) . "\n";

    // Check all inventories across warehouses for this product
    $allInvs = App\Models\Inventory::where('product_id', $item->product_id)->get();
    foreach ($allInvs as $aInv) {
        $aWh = App\Models\Warehouse::find($aInv->warehouse_id);
        echo "  Inventory table stock in warehouse {$aInv->warehouse_id} (" . ($aWh ? $aWh->code : '') . "): {$aInv->stock}\n";
    }

    // Check ProductItem table count (in_stock) per warehouse
    $piCounts = App\Models\ProductItem::where('product_id', $item->product_id)
        ->where('status', 'in_stock')
        ->select('warehouse_id', \DB::raw('count(*) as cnt'))
        ->groupBy('warehouse_id')
        ->get();
    foreach ($piCounts as $piC) {
        $aWh = App\Models\Warehouse::find($piC->warehouse_id);
        echo "  ProductItem in_stock count in warehouse {$piC->warehouse_id} (" . ($aWh ? $aWh->code : '') . "): {$piC->cnt}\n";
    }
}

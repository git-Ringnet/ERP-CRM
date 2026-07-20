<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$export = App\Models\Export::find(51);
$sale = App\Models\Sale::find(85);

echo "Export code: {$export->code}, Status: {$export->status}, WarehouseID: {$export->warehouse_id}\n";
echo "Sale code: {$sale->code}, ProjectID: {$sale->project_id}\n";

foreach ($export->items as $item) {
    echo "Item product_id: {$item->product_id} ({$item->product->code})\n";

    // Priority 1
    $p1 = App\Models\ProductItem::where('product_id', $item->product_id)
        ->where('status', App\Models\ProductItem::STATUS_IN_STOCK)
        ->whereHas('import.purchaseOrder.items.saleOrderRequestItem.saleOrderRequest', function ($q) use ($sale) {
            $q->where('sale_id', $sale->id);
            if ($sale->project_id) {
                $q->orWhereHas('sale', function ($sq) use ($sale) {
                    $sq->where('project_id', $sale->project_id);
                });
            }
        })
        ->pluck('id', 'sku')
        ->toArray();
    echo "  Priority 1 matches: " . json_encode($p1) . "\n";

    // All in stock for this product
    $allStock = App\Models\ProductItem::where('product_id', $item->product_id)
        ->where('status', App\Models\ProductItem::STATUS_IN_STOCK)
        ->pluck('id', 'sku')
        ->toArray();
    echo "  All in_stock for product: " . json_encode($allStock) . "\n";
}

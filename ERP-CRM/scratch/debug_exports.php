<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$exports = App\Models\Export::with(['items.product', 'warehouse'])->get();

foreach ($exports as $export) {
    echo "Export ID: {$export->id}, Code: {$export->code}, Status: {$export->status}, RefType: {$export->reference_type}, RefID: {$export->reference_id}\n";
    foreach ($export->items as $item) {
        echo "   Item ID: {$item->id}, Product: {$item->product?->code} ({$item->product?->name}), Qty: {$item->quantity}, SerialNumberJSON: " . var_export($item->serial_number, true) . "\n";
        
        if ($export->status === 'completed') {
            $productItems = App\Models\ProductItem::where('export_id', $export->id)->where('product_id', $item->product_id)->get();
            echo "   -> ProductItems sold (count: " . $productItems->count() . "): " . $productItems->pluck('sku')->implode(', ') . "\n";
        } else {
            if (!empty($item->serial_number)) {
                $ids = json_decode($item->serial_number, true);
                if (is_array($ids)) {
                    $productItems = App\Models\ProductItem::whereIn('id', $ids)->get();
                    echo "   -> ProductItems mapped (count: " . $productItems->count() . "): " . $productItems->pluck('sku')->implode(', ') . "\n";
                }
            } else {
                echo "   -> No serials mapped!\n";
            }
        }
    }
}

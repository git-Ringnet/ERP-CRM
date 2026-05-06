<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::whereNotNull('warranty_months')->limit(5)->get();
foreach ($products as $p) {
    echo "Product: {$p->code} - {$p->name} | Warranty: {$p->warranty_months}\n";
}

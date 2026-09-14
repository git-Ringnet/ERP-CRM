<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\User;
use App\Services\BomParserService;

// Find or create test project with bom_data
$project = Project::first();
if ($project) {
    $project->bom_data = "STT\tPart Number\tModel\tSố lượng\tĐơn giá\n1\tAW210040\tAirEngine 5760-51\t2\t15,000,000\n2\tFG-60F-BDL\tFortiGate 60F Hardware\t1\t12,500,000";
    $project->save();

    echo "Project ID {$project->id} updated with bom_data.\n";

    // Test BomParserService
    $service = app(BomParserService::class);
    $items = $service->parseFromProjects(collect([$project]));

    echo "Parsed " . count($items) . " items from project.\n";
    foreach ($items as $idx => $item) {
        echo "Item #{$idx}: [{$item['code']}] {$item['name']} - Qty: {$item['quantity']}, Price: {$item['price']}, Matched: " . ($item['is_matched'] ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "No project found.\n";
}

// Test direct parse with mixed content
$service = app(BomParserService::class);
$customBom = "2x FG-60F-BDL\nCisco C9200L-24P-4G-E (Qty: 3, Price: 25.000.000)\nAW210040 - Huawei AP - 5 pcs @ 14,000,000";
$parsed = $service->parse($customBom, 999);
echo "\nParsed custom BOM (" . count($parsed) . " items):\n";
foreach ($parsed as $idx => $item) {
    echo "Custom #{$idx}: [{$item['code']}] {$item['name']} - Qty: {$item['quantity']}, Price: {$item['price']}, IsNew: " . ($item['is_new'] ? 'YES' : 'NO') . "\n";
}

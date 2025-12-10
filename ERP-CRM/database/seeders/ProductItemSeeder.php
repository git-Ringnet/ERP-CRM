<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Requirements: 3.1 - Sample product items with SKUs and price tiers
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Sample product items for FortiGate 60F (product_id = 4)
        $fortigate60fItems = [
            [
                'product_id' => 4,
                'sku' => 'FG-60F-BDL-950-DD',
                'description' => 'FortiGate 60F Hardware plus 24x7 FortiCare and FortiGuard Unified Threat Protection (UTP)',
                'cost_usd' => 1475,
                'price_tiers' => json_encode([
                    ['name' => '1yr', 'price' => 2055],
                    ['name' => '2yr', 'price' => 4321],
                    ['name' => '3yr', 'price' => 5015],
                    ['name' => '4yr', 'price' => 5375],
                    ['name' => '5yr', 'price' => 7375],
                ]),
                'quantity' => 1,
                'comments' => 'New Product: Call for Availability',
                'warehouse_id' => 1,
                'inventory_transaction_id' => null,
                'status' => 'in_stock',
            ],
            [
                'product_id' => 4,
                'sku' => 'FG-60F-BDL-811-DD',
                'description' => 'FortiGate 60F Hardware plus 24x7 FortiCare and FortiGuard Enterprise Protection',
                'cost_usd' => 1475,
                'price_tiers' => json_encode([
                    ['name' => '1yr', 'price' => 2434],
                    ['name' => '2yr', 'price' => 4321],
                    ['name' => '3yr', 'price' => 6269],
                ]),
                'quantity' => 1,
                'comments' => null,
                'warehouse_id' => 1,
                'inventory_transaction_id' => null,
                'status' => 'in_stock',
            ],
            [
                'product_id' => 4,
                'sku' => 'NOSKU-4-001',
                'description' => 'FortiGate 60F - No SKU assigned',
                'cost_usd' => 1475,
                'price_tiers' => json_encode([
                    ['name' => '1yr', 'price' => 2055],
                ]),
                'quantity' => 1,
                'comments' => 'Auto-generated NO_SKU',
                'warehouse_id' => 1,
                'inventory_transaction_id' => null,
                'status' => 'in_stock',
            ],
        ];

        // Sample product items for FortiGate 100F (product_id = 5)
        $fortigate100fItems = [
            [
                'product_id' => 5,
                'sku' => 'FG-100F-BDL-950-DD',
                'description' => 'FortiGate 100F Hardware plus 24x7 FortiCare and FortiGuard UTP',
                'cost_usd' => 3500,
                'price_tiers' => json_encode([
                    ['name' => '1yr', 'price' => 4500],
                    ['name' => '2yr', 'price' => 8500],
                    ['name' => '3yr', 'price' => 12000],
                    ['name' => '4yr', 'price' => 15000],
                    ['name' => '5yr', 'price' => 18000],
                ]),
                'quantity' => 1,
                'comments' => null,
                'warehouse_id' => 1,
                'inventory_transaction_id' => null,
                'status' => 'in_stock',
            ],
            [
                'product_id' => 5,
                'sku' => 'FG-100F-BDL-811-DD',
                'description' => 'FortiGate 100F Hardware plus Enterprise Protection',
                'cost_usd' => 3500,
                'price_tiers' => json_encode([
                    ['name' => '1yr', 'price' => 5200],
                    ['name' => '2yr', 'price' => 9800],
                    ['name' => '3yr', 'price' => 14000],
                ]),
                'quantity' => 1,
                'comments' => null,
                'warehouse_id' => 1,
                'inventory_transaction_id' => null,
                'status' => 'sold',
            ],
        ];

        // Sample items for Laptop Dell (product_id = 1)
        $laptopItems = [
            [
                'product_id' => 1,
                'sku' => 'DELL-INS15-001',
                'description' => 'Dell Inspiron 15 - Unit 1',
                'cost_usd' => 450,
                'price_tiers' => null,
                'quantity' => 1,
                'comments' => null,
                'warehouse_id' => 1,
                'inventory_transaction_id' => null,
                'status' => 'in_stock',
            ],
            [
                'product_id' => 1,
                'sku' => 'DELL-INS15-002',
                'description' => 'Dell Inspiron 15 - Unit 2',
                'cost_usd' => 450,
                'price_tiers' => null,
                'quantity' => 1,
                'comments' => null,
                'warehouse_id' => 1,
                'inventory_transaction_id' => null,
                'status' => 'in_stock',
            ],
        ];

        $allItems = array_merge($fortigate60fItems, $fortigate100fItems, $laptopItems);

        foreach ($allItems as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            DB::table('product_items')->insert($item);
        }
    }
}

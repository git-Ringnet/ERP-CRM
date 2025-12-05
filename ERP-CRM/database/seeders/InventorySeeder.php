<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::where('status', 'active')->get();
        $products = Product::all();

        if ($warehouses->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No warehouses or products found. Skipping inventory seeding.');
            return;
        }

        // Create inventory for each product in each warehouse
        foreach ($warehouses as $warehouse) {
            // Skip virtual warehouses for physical products
            if ($warehouse->type === 'virtual') {
                continue;
            }

            // Randomly select 60-80% of products for this warehouse
            $warehouseProducts = $products->random(rand((int)($products->count() * 0.6), (int)($products->count() * 0.8)));

            foreach ($warehouseProducts as $product) {
                // Generate random stock levels
                $minStock = rand(10, 50);
                $stock = rand(0, 200);
                
                // Some items will be low stock
                if (rand(1, 10) <= 3) { // 30% chance of low stock
                    $stock = rand(0, $minStock - 1);
                }

                // Generate expiry date for some products
                $expiryDate = null;
                if (rand(1, 10) <= 4) { // 40% have expiry date
                    $daysUntilExpiry = rand(-30, 180); // Some expired, some expiring soon, some far future
                    $expiryDate = Carbon::now()->addDays($daysUntilExpiry);
                }

                Inventory::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => $stock,
                    'min_stock' => $minStock,
                    'avg_cost' => $product->price * rand(60, 85) / 100, // 60-85% of selling price
                    'expiry_date' => $expiryDate,
                    'warranty_months' => rand(1, 10) <= 6 ? rand(6, 36) : null, // 60% have warranty
                ]);
            }
        }

        $this->command->info('Inventory seeded successfully!');
    }
}

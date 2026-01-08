<?php

namespace Database\Seeders;

use App\Models\Import;
use App\Models\Export;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;

class InventoryTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $products = Product::all();
        $users = User::all();

        if ($warehouses->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No warehouses, products, or users found. Skipping InventoryTransactionSeeder.');
            return;
        }

        $transactionService = app(TransactionService::class);

        // Create 10-15 import transactions
        $this->command->info('Creating import transactions...');
        for ($i = 0; $i < rand(10, 15); $i++) {
            $warehouse = $warehouses->random();
            $employee = $users->random();
            $date = now()->subDays(rand(1, 60));
            
            // Select 2-5 random products
            $selectedProducts = $products->random(rand(2, 5));
            $items = [];
            $totalQty = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(10, 100);
                $totalQty += $quantity;
                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit' => $product->unit,
                ];
            }

            try {
                $transactionService->processImport([
                    'warehouse_id' => $warehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'items' => $items,
                    'note' => 'Nhập kho từ nhà cung cấp - Seeder',
                ]);
            } catch (\Exception $e) {
                $this->command->warn("Failed to create import transaction: {$e->getMessage()}");
            }
        }

        // Create 8-12 export transactions
        $this->command->info('Creating export transactions...');
        for ($i = 0; $i < rand(8, 12); $i++) {
            $warehouse = $warehouses->random();
            $employee = $users->random();
            $date = now()->subDays(rand(1, 50));
            
            // Get products that have inventory in this warehouse
            $availableProducts = $products->filter(function ($product) use ($warehouse) {
                $inventory = $product->inventories()->where('warehouse_id', $warehouse->id)->first();
                return $inventory && $inventory->stock > 10;
            });

            if ($availableProducts->isEmpty()) {
                continue;
            }

            // Select 1-3 products
            $selectedProducts = $availableProducts->random(min(rand(1, 3), $availableProducts->count()));
            $items = [];
            $totalQty = 0;

            foreach ($selectedProducts as $product) {
                $inventory = $product->inventories()->where('warehouse_id', $warehouse->id)->first();
                $maxQty = min($inventory->stock - 5, 50); // Leave some stock
                
                if ($maxQty <= 0) {
                    continue;
                }

                $quantity = rand(1, $maxQty);
                $totalQty += $quantity;
                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit' => $product->unit,
                    'warehouse_id' => $warehouse->id,
                ];
            }

            if (empty($items)) {
                continue;
            }

            try {
                $transactionService->processExport([
                    'warehouse_id' => $warehouse->id,
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'items' => $items,
                    'note' => 'Xuất kho cho khách hàng - Seeder',
                ]);
            } catch (\Exception $e) {
                $this->command->warn("Failed to create export transaction: {$e->getMessage()}");
            }
        }

        // Create 5-8 transfer transactions
        $this->command->info('Creating transfer transactions...');
        if ($warehouses->count() >= 2) {
            for ($i = 0; $i < rand(5, 8); $i++) {
                $fromWarehouse = $warehouses->random();
                $toWarehouse = $warehouses->where('id', '!=', $fromWarehouse->id)->random();
                $employee = $users->random();
                $date = now()->subDays(rand(1, 40));
                
                // Get products that have inventory in source warehouse
                $availableProducts = $products->filter(function ($product) use ($fromWarehouse) {
                    $inventory = $product->inventories()->where('warehouse_id', $fromWarehouse->id)->first();
                    return $inventory && $inventory->stock > 5;
                });

                if ($availableProducts->isEmpty()) {
                    continue;
                }

                // Select 1-2 products
                $selectedProducts = $availableProducts->random(min(rand(1, 2), $availableProducts->count()));
                $items = [];
                $totalQty = 0;

                foreach ($selectedProducts as $product) {
                    $inventory = $product->inventories()->where('warehouse_id', $fromWarehouse->id)->first();
                    $maxQty = min($inventory->stock - 3, 30); // Leave some stock
                    
                    if ($maxQty <= 0) {
                        continue;
                    }

                    $quantity = rand(1, $maxQty);
                    $totalQty += $quantity;
                    $items[] = [
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit' => $product->unit,
                        'warehouse_id' => $fromWarehouse->id,
                        'to_warehouse_id' => $toWarehouse->id,
                    ];
                }

                if (empty($items)) {
                    continue;
                }

                try {
                    $transactionService->processTransfer([
                        'from_warehouse_id' => $fromWarehouse->id,
                        'to_warehouse_id' => $toWarehouse->id,
                        'employee_id' => $employee->id,
                        'date' => $date,
                        'items' => $items,
                        'note' => 'Chuyển kho nội bộ - Seeder',
                    ]);
                } catch (\Exception $e) {
                    $this->command->warn("Failed to create transfer transaction: {$e->getMessage()}");
                }
            }
        }

        $this->command->info('InventoryTransactionSeeder completed successfully.');
    }
}

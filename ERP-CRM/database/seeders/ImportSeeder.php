<?php

namespace Database\Seeders;

use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $users = User::all();
        $products = Product::all();

        if ($warehouses->isEmpty() || $users->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Please seed warehouses, users, and products first.');
            return;
        }

        $statuses = ['pending', 'completed', 'cancelled', 'rejected'];
        
        for ($i = 1; $i <= 20; $i++) {
            $warehouse = $warehouses->random();
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            
            $import = Import::create([
                'code' => 'IMP' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'warehouse_id' => $warehouse->id,
                'date' => now()->subDays(rand(0, 90)),
                'employee_id' => $user->id,
                'total_qty' => 0,
                'note' => 'Sample import #' . $i,
                'status' => $status,
            ]);

            // Create 2-5 import items
            $itemCount = rand(2, 5);
            $totalQty = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(5, 50);
                $totalQty += $quantity;
                
                ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit' => $product->unit ?? 'pcs',
                    'cost' => rand(100, 1000) * 1000,
                    'serial_number' => rand(0, 1) ? 'SN-IMP-' . $i . '-' . $j : null,
                    'comments' => rand(0, 1) ? 'Import item comment' : null,
                ]);
            }
            
            // Update total quantity
            $import->update(['total_qty' => $totalQty]);
        }

        $this->command->info('Created 20 sample imports with items.');
    }
}

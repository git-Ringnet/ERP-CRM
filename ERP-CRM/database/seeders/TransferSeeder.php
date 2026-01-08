<?php

namespace Database\Seeders;

use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $users = User::all();
        $products = Product::all();

        if ($warehouses->count() < 2 || $users->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Please seed at least 2 warehouses, users, and products first.');
            return;
        }

        $statuses = ['pending', 'completed', 'cancelled', 'rejected'];
        
        for ($i = 1; $i <= 10; $i++) {
            // Get two different warehouses
            $fromWarehouse = $warehouses->random();
            $toWarehouse = $warehouses->where('id', '!=', $fromWarehouse->id)->random();
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            
            $transfer = Transfer::create([
                'code' => 'TRF' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'date' => now()->subDays(rand(0, 90)),
                'employee_id' => $user->id,
                'total_qty' => 0,
                'note' => 'Sample transfer #' . $i,
                'status' => $status,
            ]);

            // Create 2-4 transfer items
            $itemCount = rand(2, 4);
            $totalQty = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(1, 30);
                $totalQty += $quantity;
                
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit' => $product->unit ?? 'pcs',
                    'serial_number' => rand(0, 1) ? 'SN-TRF-' . $i . '-' . $j : null,
                    'comments' => rand(0, 1) ? 'Transfer item comment' : null,
                ]);
            }
            
            // Update total quantity
            $transfer->update(['total_qty' => $totalQty]);
        }

        $this->command->info('Created 10 sample transfers with items.');
    }
}

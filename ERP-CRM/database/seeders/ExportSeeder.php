<?php

namespace Database\Seeders;

use App\Models\Export;
use App\Models\ExportItem;
use App\Models\Warehouse;
use App\Models\Project;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ExportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $projects = Project::all();
        $users = User::all();
        $products = Product::all();

        if ($warehouses->isEmpty() || $users->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Please seed warehouses, users, and products first.');
            return;
        }

        $statuses = ['pending', 'completed', 'cancelled', 'rejected'];
        
        for ($i = 1; $i <= 15; $i++) {
            $warehouse = $warehouses->random();
            $project = $projects->isNotEmpty() ? $projects->random() : null;
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            
            $export = Export::create([
                'code' => 'EXP' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'warehouse_id' => $warehouse->id,
                'project_id' => $project?->id,
                'date' => now()->subDays(rand(0, 90)),
                'employee_id' => $user->id,
                'total_qty' => 0,
                'note' => 'Sample export #' . $i,
                'status' => $status,
            ]);

            // Create 2-5 export items
            $itemCount = rand(2, 5);
            $totalQty = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(1, 20);
                $totalQty += $quantity;
                
                ExportItem::create([
                    'export_id' => $export->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit' => $product->unit ?? 'pcs',
                    'serial_number' => rand(0, 1) ? 'SN-EXP-' . $i . '-' . $j : null,
                    'comments' => rand(0, 1) ? 'Export item comment' : null,
                ]);
            }
            
            // Update total quantity
            $export->update(['total_qty' => $totalQty]);
        }

        $this->command->info('Created 15 sample exports with items.');
    }
}

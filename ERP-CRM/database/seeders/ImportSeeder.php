<?php

namespace Database\Seeders;

use App\Models\Import;
use App\Models\ImportItem;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Inventory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ưu tiên 4 kho vận hành chính trên giao diện ERP
        $warehouses = Warehouse::whereIn('code', ['WH_RUNRATE', 'WH_PROJECT', 'WH_LICENSE', 'WH_WARRANTY'])->get();
        if ($warehouses->isEmpty()) {
            $warehouses = Warehouse::all();
        }

        $users = User::all();
        $products = Product::all();

        if ($warehouses->isEmpty() || $users->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Please seed warehouses, users, and products first.');
            return;
        }

        $statuses = ['pending', 'completed', 'completed', 'rejected', 'cancelled'];
        $lastNum = (int) Import::count();
        
        for ($i = 1; $i <= 10; $i++) {
            $code = 'IMP' . str_pad($lastNum + $i, 6, '0', STR_PAD_LEFT);
            if (Import::where('code', $code)->exists()) {
                continue;
            }

            $warehouse = $warehouses->random();
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            $importDate = now()->subDays(rand(1, 60));
            
            $import = Import::create([
                'code' => $code,
                'warehouse_id' => $warehouse->id,
                'date' => $importDate,
                'employee_id' => $user->id,
                'total_qty' => 0,
                'note' => 'Phiếu nhập kho mẫu #' . ($lastNum + $i),
                'status' => $status,
            ]);

            // Create 2-4 import items
            $itemCount = rand(2, 4);
            $totalQty = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(5, 30);
                $totalQty += $quantity;
                $cost = rand(100, 1000) * 1000;
                
                $item = ImportItem::create([
                    'import_id' => $import->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit' => $product->unit ?? 'pcs',
                    'cost' => $cost,
                    'serial_number' => 'SN-IMP-' . ($lastNum + $i) . '-' . ($j + 1),
                    'comments' => 'Hàng mẫu nhập kho #' . $import->code,
                    'processed_at' => ($status === 'completed') ? $importDate : null,
                ]);

                // Nếu phiếu nhập đã hoàn thành -> tự động sinh ProductItem (in_stock) và cập nhật Inventory
                if ($status === 'completed') {
                    for ($k = 1; $k <= $quantity; $k++) {
                        $sku = 'SN-' . $import->code . '-' . $item->id . '-' . str_pad($k, 3, '0', STR_PAD_LEFT);
                        ProductItem::create([
                            'product_id' => $product->id,
                            'warehouse_id' => $warehouse->id,
                            'import_id' => $import->id,
                            'sku' => $sku,
                            'quantity' => 1,
                            'cost_usd' => round($cost / 25000, 2),
                            'comments' => $item->comments,
                            'status' => ProductItem::STATUS_IN_STOCK,
                            'created_at' => $importDate,
                            'updated_at' => $importDate,
                        ]);
                    }

                    // Cập nhật bảng tồn kho tổng hợp (inventories)
                    $inventory = Inventory::firstOrNew([
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ]);
                    $inventory->stock = ($inventory->stock ?? 0) + $quantity;
                    $inventory->avg_cost = $cost;
                    $inventory->save();
                }
            }
            
            // Update total quantity
            $import->update(['total_qty' => $totalQty]);

            // Tạo bút toán kế toán cho seeder (Lịch sử: Tạo mới & Duyệt)
            try {
                $import->refresh()->load(['items', 'supplier', 'warehouse']);
                $journalService = app(\App\Services\WarehouseJournalService::class);
                $journalService->createForImport($import, 'create');
                if ($import->status === 'completed') {
                    $journalService->createForImport($import, 'approve');
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Seeder error for import {$import->code}: " . $e->getMessage());
            }
        }

        $this->command->info('Đã tạo 10 phiếu nhập mẫu và đồng bộ tồn kho đầy đủ.');
    }
}


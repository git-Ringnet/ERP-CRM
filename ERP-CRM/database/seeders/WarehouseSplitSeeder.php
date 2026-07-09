<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WarehouseSplitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Create the 4 warehouses
        $warehouses = [
            'WH_PROJECT' => [
                'code' => 'WH_PROJECT',
                'name' => 'Kho dự án',
                'type' => 'physical',
                'status' => 'active',
                'product_type' => 'Thiết bị dự án',
                'note' => 'Kho chứa hàng thiết bị dự án',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'WH_RUNRATE' => [
                'code' => 'WH_RUNRATE',
                'name' => 'Kho runrate',
                'type' => 'physical',
                'status' => 'active',
                'product_type' => 'Thiết bị runrate',
                'note' => 'Kho chứa hàng runrate',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'WH_LICENSE' => [
                'code' => 'WH_LICENSE',
                'name' => 'Kho license',
                'type' => 'virtual',
                'status' => 'active',
                'product_type' => 'License & Subscription',
                'note' => 'Kho chứa hàng license',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            'WH_WARRANTY' => [
                'code' => 'WH_WARRANTY',
                'name' => 'Kho bảo hành',
                'type' => 'physical',
                'status' => 'active',
                'product_type' => 'Thiết bị bảo hành',
                'note' => 'Kho chứa hàng bảo hành',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $warehouseIds = [];
        foreach ($warehouses as $code => $data) {
            DB::table('warehouses')->updateOrInsert(['code' => $code], $data);
            $warehouseIds[$code] = DB::table('warehouses')->where('code', $code)->value('id');
        }

        // 2. Fetch project PO IDs for classification
        $projectPoIds = DB::table('purchase_order_items as poi')
            ->join('sale_order_request_items as sori', 'poi.sale_order_request_item_id', '=', 'sori.id')
            ->join('sale_order_requests as sor', 'sori.sale_order_request_id', '=', 'sor.id')
            ->whereNotNull('sor.sale_id')
            ->groupBy('purchase_order_id')
            ->having(DB::raw('COUNT(DISTINCT sale_id)'), '=', 1)
            ->pluck('purchase_order_id')
            ->toArray();

        // 3. Classify all product items and assign them to the correct warehouses
        $items = DB::table('product_items')
            ->leftJoin('products', 'product_items.product_id', '=', 'products.id')
            ->leftJoin('imports', 'product_items.import_id', '=', 'imports.id')
            ->select('product_items.*', 'products.code as product_code', 'products.name as product_name', 'imports.reference_type as import_reference_type', 'imports.reference_id as import_reference_id')
            ->get();

        foreach ($items as $item) {
            $code = 'WH_RUNRATE';

            if ($item->product_code) {
                // A. Warranty
                if (str_ends_with($item->product_code, 'R') || 
                    str_ends_with($item->product_code, 'NFR') ||
                    stripos($item->product_code, 'DRMA') !== false ||
                    stripos($item->product_name, 'DRMA') !== false) {
                    $code = 'WH_WARRANTY';
                }
                // B. License
                elseif (str_starts_with($item->product_code, 'FC-') || 
                    stripos($item->product_code, 'license') !== false || 
                    stripos($item->product_name, 'license') !== false || 
                    stripos($item->product_code, 'e-license') !== false || 
                    stripos($item->product_name, 'e-license') !== false || 
                    stripos($item->product_code, 'subscription') !== false || 
                    stripos($item->product_name, 'subscription') !== false || 
                    stripos($item->product_code, 'renewal') !== false || 
                    stripos($item->product_name, 'renewal') !== false) {
                    $code = 'WH_LICENSE';
                }
                else {
                    // Check PO items with License type
                    $poItem = null;
                    if ($item->import_reference_type === 'purchase_order' && $item->import_reference_id) {
                        $poItem = DB::table('purchase_order_items as poi')
                            ->join('sale_order_request_items as sori', 'poi.sale_order_request_item_id', '=', 'sori.id')
                            ->where('poi.purchase_order_id', $item->import_reference_id)
                            ->where('poi.product_id', $item->product_id)
                            ->where('sori.type', 'License')
                            ->first();
                    }

                    if ($poItem) {
                        $code = 'WH_LICENSE';
                    }
                    // C. Project Equipment
                    elseif ($item->import_reference_type === 'purchase_order' && in_array($item->import_reference_id, $projectPoIds)) {
                        $code = 'WH_PROJECT';
                    }
                }
            }

            // Update item in database
            DB::table('product_items')
                ->where('id', $item->id)
                ->update(['warehouse_id' => $warehouseIds[$code]]);
        }

        // 4. Truncate and rebuild the inventories table
        DB::table('inventories')->truncate();

        $stocks = DB::table('product_items')
            ->where('status', 'in_stock')
            ->select('product_id', 'warehouse_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('MAX(warranty_months) as max_warranty'), DB::raw('MAX(expiry_date) as max_expiry'))
            ->groupBy('product_id', 'warehouse_id')
            ->get();

        foreach ($stocks as $stock) {
            DB::table('inventories')->insert([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'stock' => $stock->total_qty,
                'min_stock' => 0,
                'avg_cost' => 0,
                'warranty_months' => $stock->max_warranty,
                'expiry_date' => $stock->max_expiry,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

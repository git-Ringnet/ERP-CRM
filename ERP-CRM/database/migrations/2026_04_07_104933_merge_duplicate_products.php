<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Normalize all existing product codes (UPPER + TRIM)
        DB::statement("UPDATE products SET code = UPPER(TRIM(code))");

        // 2. Identify duplicates and merge them
        $duplicates = DB::table('products')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $code = $duplicate->code;
            
            // Get all products with this code, ordered by ID (keep the oldest)
            $products = DB::table('products')
                ->where('code', $code)
                ->orderBy('id', 'asc')
                ->get();
            
            $master = $products->shift();
            $duplicateIds = $products->pluck('id')->toArray();

            // Tables to update foreign keys
            $tables = [
                'product_items',
                'sale_items',
                'purchase_order_items',
                'purchase_request_items',
                'quotation_items',
                'supplier_quotation_items',
                'supplier_price_list_items',
                'import_items',
                'export_items',
                'transfer_items',
                'damaged_goods',
                'damaged_good_items',
                'employee_assets',
                'inventory',
                'warehouse_journal_entries',
                'employee_asset_assignments'
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_id')) {
                    DB::table($table)
                        ->whereIn('product_id', $duplicateIds)
                        ->update(['product_id' => $master->id]);
                }
            }

            // Finally delete duplicates
            DB::table('products')->whereIn('id', $duplicateIds)->delete();
        }

        // 3. Add UNIQUE constraint to products.code
        Schema::table('products', function (Blueprint $table) {
            // Check if index already exists to prevent errors
            $conn = Schema::getConnection();
            $dbSchemaManager = $conn->getDoctrineSchemaManager();
            $indexes = $dbSchemaManager->listTableIndexes('products');
            
            if (!array_key_exists('products_code_unique', $indexes)) {
                $table->string('code', 150)->change(); // Increase length slightly for standard indexing
                $table->unique('code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Add import_id and export_id columns
            $table->foreignId('import_id')->nullable()->after('warehouse_id')->constrained('imports')->nullOnDelete();
            $table->foreignId('export_id')->nullable()->after('import_id')->constrained('exports')->nullOnDelete();
            
            // Drop old inventory_transaction_id column if exists
            if (Schema::hasColumn('product_items', 'inventory_transaction_id')) {
                $table->dropForeign(['inventory_transaction_id']);
                $table->dropColumn('inventory_transaction_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            // Drop new columns
            $table->dropForeign(['import_id']);
            $table->dropForeign(['export_id']);
            $table->dropColumn(['import_id', 'export_id']);
            
            // Restore old column
            $table->foreignId('inventory_transaction_id')->nullable()->after('warehouse_id');
        });
    }
};

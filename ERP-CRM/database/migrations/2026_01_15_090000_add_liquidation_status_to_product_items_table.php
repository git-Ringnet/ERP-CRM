<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE product_items MODIFY COLUMN status ENUM('in_stock', 'sold', 'damaged', 'transferred', 'liquidation') NOT NULL DEFAULT 'in_stock'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original statuses, mapping 'liquidation' back to 'in_stock' or 'damaged' if any exist before reverting?
        // For distinct separation, we probably shouldn't blindly revert without data handling, 
        // but strictly speaking, migration reversal should restore schema.
        // We will just restore schema. If there are 'liquidation' items, this might fail or truncate. 
        // Ideally we'd update them first, but for now we'll just define the schema revert.

        // Update 'liquidation' items to 'damaged' before reverting schema to avoid truncation error
        DB::table('product_items')->where('status', 'liquidation')->update(['status' => 'damaged']);

        DB::statement("ALTER TABLE product_items MODIFY COLUMN status ENUM('in_stock', 'sold', 'damaged', 'transferred') NOT NULL DEFAULT 'in_stock'");
    }
};

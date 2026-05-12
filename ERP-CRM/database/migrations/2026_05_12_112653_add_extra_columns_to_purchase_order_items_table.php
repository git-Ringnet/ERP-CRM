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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('warehouse_unit_price', 15, 4)->nullable()->after('unit');
            $table->string('status')->default('ordered')->after('note'); // ordered, shipping, received, cancelled
            $table->string('license_file')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['warehouse_unit_price', 'status', 'license_file']);
        });
    }
};

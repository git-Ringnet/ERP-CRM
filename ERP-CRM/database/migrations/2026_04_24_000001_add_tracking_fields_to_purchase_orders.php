<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('expected_arrival_date')->nullable()->after('actual_delivery');
            $table->date('manufacturer_release_date')->nullable()->after('expected_arrival_date');
            $table->foreignId('sale_id')->nullable()->after('supplier_quotation_id')->constrained('sales')->nullOnDelete();
            $table->boolean('is_hold')->default(false)->after('status');
            $table->text('hold_reason')->nullable()->after('is_hold');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn(['expected_arrival_date', 'manufacturer_release_date', 'sale_id', 'is_hold', 'hold_reason']);
        });
    }
};

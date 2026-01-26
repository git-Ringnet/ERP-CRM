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
        Schema::table('imports', function (Blueprint $table) {
            $table->decimal('shipping_cost', 15, 2)->default(0)->after('total_qty')->comment('Chi phí vận chuyển');
            $table->decimal('loading_cost', 15, 2)->default(0)->after('shipping_cost')->comment('Chi phí bốc xếp');
            $table->decimal('inspection_cost', 15, 2)->default(0)->after('loading_cost')->comment('Chi phí kiểm định');
            $table->decimal('other_cost', 15, 2)->default(0)->after('inspection_cost')->comment('Chi phí khác');
            $table->decimal('total_service_cost', 15, 2)->default(0)->after('other_cost')->comment('Tổng chi phí phục vụ');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('total_service_cost')->comment('Chiết khấu (%)');
            $table->decimal('vat_percent', 5, 2)->default(10)->after('discount_percent')->comment('VAT (%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_cost',
                'loading_cost',
                'inspection_cost',
                'other_cost',
                'total_service_cost',
                'discount_percent',
                'vat_percent',
            ]);
        });
    }
};

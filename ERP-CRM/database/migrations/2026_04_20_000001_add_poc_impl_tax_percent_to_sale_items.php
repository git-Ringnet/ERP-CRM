<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * POC / Triển khai / Thuế nhà thầu có thể nhập % ở chi phí đơn hàng — cần cột % trên sale_items để P&L & margin đồng bộ.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('technical_poc_percent', 5, 2)->nullable()->after('technical_poc_cost');
            $table->decimal('implementation_cost_percent', 5, 2)->nullable()->after('implementation_cost');
            $table->decimal('contractor_tax_percent', 5, 2)->nullable()->after('contractor_tax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'technical_poc_percent',
                'implementation_cost_percent',
                'contractor_tax_percent',
            ]);
        });
    }
};

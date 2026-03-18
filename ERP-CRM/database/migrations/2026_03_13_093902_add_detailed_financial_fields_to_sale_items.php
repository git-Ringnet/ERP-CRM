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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('usd_price', 15, 2)->default(0)->after('price');
            $table->decimal('exchange_rate', 15, 2)->default(1)->after('usd_price');
            $table->decimal('discount_rate', 5, 2)->default(0)->after('exchange_rate');
            $table->decimal('import_cost_rate', 5, 2)->default(0)->after('discount_rate');
            $table->decimal('estimated_cost_usd', 15, 2)->default(0)->after('import_cost_rate');
            
            // Detailed Expense fields for this item
            $table->decimal('finance_cost_percent', 5, 2)->default(1.00);
            $table->decimal('management_cost_percent', 5, 2)->default(1.00);
            $table->decimal('support_247_cost_percent', 5, 2)->default(0.50);
            
            $table->decimal('other_support_cost', 15, 2)->default(0);
            $table->decimal('technical_poc_cost', 15, 2)->default(0);
            $table->decimal('implementation_cost', 15, 2)->default(0);
            $table->decimal('contractor_tax', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'usd_price', 'exchange_rate', 'discount_rate', 'import_cost_rate', 
                'estimated_cost_usd', 'finance_cost_percent', 'management_cost_percent', 
                'support_247_cost_percent', 'other_support_cost', 'technical_poc_cost', 
                'implementation_cost', 'contractor_tax'
            ]);
        });
    }
};

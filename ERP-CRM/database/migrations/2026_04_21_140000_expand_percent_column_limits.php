<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand decimal limits for percent columns to avoid "Numeric value out of range" errors
     * when users enter large values (e.g., 1,000,000) into percentage fields by mistake.
     */
    public function up(): void
    {
        Schema::table('sale_expenses', function (Blueprint $table) {
            $table->decimal('percent_value', 15, 2)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('finance_cost_percent', 15, 2)->change();
            $table->decimal('overdue_interest_percent', 15, 2)->change();
            $table->decimal('management_cost_percent', 15, 2)->change();
            $table->decimal('support_247_cost_percent', 15, 2)->change();
            $table->decimal('other_support_cost', 15, 2)->change(); // This is used as percent in SaleItem model
            $table->decimal('technical_poc_percent', 15, 2)->change();
            $table->decimal('implementation_cost_percent', 15, 2)->change();
            $table->decimal('contractor_tax_percent', 15, 2)->change();
        });
    }

    public function down(): void
    {
        // Reverting to reasonable defaults if needed, though usually not desired
        Schema::table('sale_expenses', function (Blueprint $table) {
            $table->decimal('percent_value', 8, 2)->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('finance_cost_percent', 8, 2)->change();
            $table->decimal('overdue_interest_percent', 5, 2)->change();
            $table->decimal('management_cost_percent', 8, 2)->change();
            $table->decimal('support_247_cost_percent', 8, 2)->change();
            $table->decimal('other_support_cost', 8, 2)->change();
            $table->decimal('technical_poc_percent', 5, 2)->change();
            $table->decimal('implementation_cost_percent', 5, 2)->change();
            $table->decimal('contractor_tax_percent', 5, 2)->change();
        });
    }
};

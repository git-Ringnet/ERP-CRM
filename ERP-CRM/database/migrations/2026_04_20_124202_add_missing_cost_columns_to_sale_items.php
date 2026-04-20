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
            $table->decimal('finance_cost', 15, 2)->nullable()->after('finance_cost_percent');
            $table->decimal('management_cost', 15, 2)->nullable()->after('management_cost_percent');
            $table->decimal('support_247_cost', 15, 2)->nullable()->after('support_247_cost_percent');
            $table->decimal('other_support_cost_percent', 15, 2)->nullable()->after('other_support_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'finance_cost',
                'management_cost',
                'support_247_cost',
                'other_support_cost_percent'
            ]);
        });
    }
};

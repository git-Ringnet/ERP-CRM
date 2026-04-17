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
            // Thêm trường percent cho overdue interest, đặt sau overdue_interest_cost
            $table->decimal('overdue_interest_percent', 5, 2)->nullable()->after('overdue_interest_cost')
                ->comment('Tỷ lệ % lãi vay phát sinh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('overdue_interest_percent');
        });
    }
};

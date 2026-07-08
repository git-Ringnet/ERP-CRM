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
            $table->decimal('finance_cost_percent', 5, 2)->nullable()->default(null)->change();
            $table->decimal('management_cost_percent', 5, 2)->nullable()->default(null)->change();
            $table->decimal('support_247_cost_percent', 5, 2)->nullable()->default(null)->change();
        });
        
        // Reset các giá trị mặc định cũ về NULL cho các record chưa được chỉnh sửa P&L
        DB::table('sale_items')->where('finance_cost_percent', 1.00)->update(['finance_cost_percent' => null]);
        DB::table('sale_items')->where('management_cost_percent', 1.00)->update(['management_cost_percent' => null]);
        DB::table('sale_items')->where('support_247_cost_percent', 0.50)->update(['support_247_cost_percent' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('finance_cost_percent', 5, 2)->nullable()->default(1.00)->change();
            $table->decimal('management_cost_percent', 5, 2)->nullable()->default(1.00)->change();
            $table->decimal('support_247_cost_percent', 5, 2)->nullable()->default(0.50)->change();
        });
    }
};

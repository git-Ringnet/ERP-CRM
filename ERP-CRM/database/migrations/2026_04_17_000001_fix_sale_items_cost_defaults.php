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
            // Thay đổi các trường chi phí từ default sang nullable
            $table->decimal('finance_cost_percent', 5, 2)->nullable()->default(null)->change();
            $table->decimal('management_cost_percent', 5, 2)->nullable()->default(null)->change();
            $table->decimal('support_247_cost_percent', 5, 2)->nullable()->default(null)->change();
        });
        
        // Reset các giá trị mặc định cũ về NULL cho các record chưa được chỉnh sửa P&L
        DB::statement("UPDATE sale_items SET finance_cost_percent = NULL WHERE finance_cost_percent = 1.00");
        DB::statement("UPDATE sale_items SET management_cost_percent = NULL WHERE management_cost_percent = 1.00");
        DB::statement("UPDATE sale_items SET support_247_cost_percent = NULL WHERE support_247_cost_percent = 0.50");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('finance_cost_percent', 5, 2)->default(1.00)->change();
            $table->decimal('management_cost_percent', 5, 2)->default(1.00)->change();
            $table->decimal('support_247_cost_percent', 5, 2)->default(0.50)->change();
        });
    }
};

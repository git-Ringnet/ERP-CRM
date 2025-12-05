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
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('cost', 15, 2)->default(0)->after('total')->comment('Chi phí bán hàng');
            $table->decimal('margin_percent', 5, 2)->default(0)->after('margin')->comment('Tỷ lệ lợi nhuận (%)');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('margin_percent')->comment('Số tiền đã thanh toán');
            $table->decimal('debt_amount', 15, 2)->default(0)->after('paid_amount')->comment('Công nợ còn lại');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('debt_amount')->comment('Trạng thái thanh toán');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['cost', 'margin_percent', 'paid_amount', 'debt_amount', 'payment_status']);
        });
    }
};

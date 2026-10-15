<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PO đặt trước cho đơn hàng khi chưa có SO/P&L: cho tạo PO linh hoạt nhưng chặn
     * xuất hàng nhập từ PO này cho tới khi PO được liên kết SO có P&L đã duyệt.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('awaiting_so_link')->default(false)->after('sale_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('awaiting_so_link');
        });
    }
};

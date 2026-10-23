<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Đợt thanh toán "Trước khi xuất hàng" (BEFORE_EXPORT) trước đây bị tính hạn từ ngày duyệt P&L,
     * nên đơn chưa đặt hàng đã bị báo quá hạn. Đợt này chỉ là điều kiện chặn xuất kho:
     * bỏ hạn thanh toán và đưa các đợt bị gắn quá hạn sai về chờ thanh toán.
     */
    public function up(): void
    {
        DB::table('sale_payment_schedules')
            ->where('trigger_type', 'BEFORE_EXPORT')
            ->where('status', 'overdue')
            ->update(['status' => 'pending']);

        DB::table('sale_payment_schedules')
            ->where('trigger_type', 'BEFORE_EXPORT')
            ->whereNotNull('due_date')
            ->update(['due_date' => null, 'trigger_date' => null]);
    }

    public function down(): void
    {
        // Không khôi phục hạn cũ (dữ liệu tính sai)
    }
};

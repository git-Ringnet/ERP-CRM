<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trang chi tiết sự kiện từng ghi đè status = 'overdue' khi quá hạn, làm mất trạng thái thật
 * (yêu cầu chưa ai nhận không còn ở "Mới tiếp nhận" nên không tự nhận việc được).
 * Quá hạn giờ chỉ là cảnh báo hiển thị → khôi phục status cho các bản ghi bị ghi đè.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('marketing_requests')
            ->where('status', 'overdue')
            ->whereNull('assigned_to')
            ->update(['status' => 'received']);

        DB::table('marketing_requests')
            ->where('status', 'overdue')
            ->whereNotNull('assigned_to')
            ->update(['status' => 'in_progress']);
    }

    public function down(): void
    {
        // Không khôi phục lại trạng thái 'overdue' (đó là lỗi dữ liệu)
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cột sku lưu S/N. S/N nhập cách nhau bằng khoảng trắng tính là 1 S/N (giống Yêu cầu đặt hàng),
 * VD license áp dụng cho nhiều thiết bị dài hơn 100 ký tự → nới bằng cột S/N của Yêu cầu đặt hàng.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE product_items MODIFY sku VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL');
    }

    public function down(): void
    {
        // Không thu hẹp lại: S/N đã lưu dài hơn 100 ký tự sẽ bị lỗi hoặc mất dữ liệu.
    }
};

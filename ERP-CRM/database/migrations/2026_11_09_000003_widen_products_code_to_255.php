<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY code VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL');
    }

    public function down(): void
    {
        // Không thu hẹp lại: mã sản phẩm dài hơn giới hạn cũ sẽ bị cắt.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index gộp cho các truy vấn chuông thông báo:
     *   WHERE user_id = ? AND is_read = 0            (đếm chưa đọc)
     *   WHERE user_id = ? ORDER BY created_at DESC   (danh sách gần nhất)
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'is_read', 'created_at'], 'notifications_user_read_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_user_read_created_index');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Sử dụng câu lệnh raw SQL để sửa đổi cột action từ enum sang varchar(255) 
        // nhằm tránh lỗi Doctrine DBAL không hỗ trợ chuyển đổi ENUM trực tiếp.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE approval_histories MODIFY COLUMN action VARCHAR(255) NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('approval_histories', function (Blueprint $table) {
                $table->enum('action', ['pending', 'approved', 'rejected'])->change();
            });
        }
    }
};

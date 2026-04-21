<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chuyển cột status từ ENUM sang string (VARCHAR) để linh hoạt hơn.
     * Cột hiện tại thiếu giá trị 'rejected' nên gây lỗi khi workflow từ chối duyệt.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('status', 50)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Khi quay lại ENUM thì cần liệt kê đủ các giá trị đang có trong DB để tránh mất dữ liệu
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'shipping', 'completed', 'cancelled', 'rejected'])->default('pending')->change();
        });
    }
};

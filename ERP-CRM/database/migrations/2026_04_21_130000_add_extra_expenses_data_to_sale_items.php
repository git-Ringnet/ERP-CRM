<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chi phí extra (VND cố định) cần lưu per-item thay vì chỉ lưu tổng ở sale_expenses.
     * JSON format: {"expense_id": amount, ...}
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->json('extra_expenses_data')->nullable()->after('contractor_tax_percent');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('extra_expenses_data');
        });
    }
};

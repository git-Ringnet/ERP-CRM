<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thuế nhà thầu: cho phép bật/tắt per-item thay vì auto áp dụng toàn bộ đơn.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('contractor_tax_enabled')->default(false)->after('contractor_tax_percent');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('contractor_tax_enabled');
        });
    }
};

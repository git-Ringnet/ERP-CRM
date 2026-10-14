<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thuế cloud: tương tự Thuế nhà thầu, bật/tắt theo từng dòng sản phẩm.
     * Công thức: Giá vốn dòng / (1 - 5% - 10%) * 5%.
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('cloud_tax', 15, 2)->nullable()->default(0)->after('contractor_tax_enabled');
            $table->boolean('cloud_tax_enabled')->default(false)->after('cloud_tax');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['cloud_tax', 'cloud_tax_enabled']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'min_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('min_price', 15, 2)->nullable()->after('brand')->comment('Mức giá bán sàn tối thiểu (Min Price quy định)');
                $table->string('min_price_currency', 10)->default('VND')->after('min_price')->comment('Loại tiền tệ giá min: VND hoặc USD');
            });
        }

        if (Schema::hasTable('supplier_price_list_items') && !Schema::hasColumn('supplier_price_list_items', 'min_price')) {
            Schema::table('supplier_price_list_items', function (Blueprint $table) {
                $table->decimal('min_price', 15, 2)->nullable()->after('list_price')->comment('Mức giá bán sàn tối thiểu');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'min_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['min_price', 'min_price_currency']);
            });
        }

        if (Schema::hasTable('supplier_price_list_items') && Schema::hasColumn('supplier_price_list_items', 'min_price')) {
            Schema::table('supplier_price_list_items', function (Blueprint $table) {
                $table->dropColumn(['min_price']);
            });
        }
    }
};

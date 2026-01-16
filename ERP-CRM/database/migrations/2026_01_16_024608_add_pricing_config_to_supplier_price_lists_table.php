<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('supplier_price_lists', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_price_lists', 'supplier_discount_percent')) {
                $table->decimal('supplier_discount_percent', 8, 2)->default(0)->after('price_type');
            }
            if (!Schema::hasColumn('supplier_price_lists', 'margin_percent')) {
                $table->decimal('margin_percent', 8, 2)->default(0)->after('supplier_discount_percent');
            }
            if (!Schema::hasColumn('supplier_price_lists', 'shipping_percent')) {
                $table->decimal('shipping_percent', 8, 2)->default(0)->after('margin_percent');
            }
            if (!Schema::hasColumn('supplier_price_lists', 'shipping_fixed')) {
                $table->decimal('shipping_fixed', 15, 2)->default(0)->after('shipping_percent');
            }
            if (!Schema::hasColumn('supplier_price_lists', 'other_fees')) {
                $table->decimal('other_fees', 15, 2)->default(0)->after('shipping_fixed');
            }
            if (!Schema::hasColumn('supplier_price_lists', 'pricing_formula')) {
                $table->json('pricing_formula')->nullable()->after('other_fees');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_price_lists', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_discount_percent',
                'margin_percent',
                'shipping_percent',
                'shipping_fixed',
                'other_fees',
                'pricing_formula'
            ]);
        });
    }
};

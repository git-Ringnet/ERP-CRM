<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('supplier_price_lists', function (Blueprint $table) {
            // Pricing configuration fields
            $table->decimal('supplier_discount_percent', 5, 2)->default(0)->after('price_type')
                ->comment('Chiết khấu từ NCC (%)');
            $table->decimal('shipping_percent', 5, 2)->default(0)->after('supplier_discount_percent')
                ->comment('Phí ship theo % giá');
            $table->decimal('shipping_fixed', 15, 2)->default(0)->after('shipping_percent')
                ->comment('Phí ship cố định (USD)');
            $table->decimal('margin_percent', 5, 2)->default(0)->after('shipping_fixed')
                ->comment('Margin/Markup (%)');
            $table->decimal('other_fees', 15, 2)->default(0)->after('margin_percent')
                ->comment('Phí khác (USD)');
            $table->json('pricing_formula')->nullable()->after('other_fees')
                ->comment('Công thức tính giá tùy chỉnh');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_price_lists', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_discount_percent',
                'shipping_percent',
                'shipping_fixed',
                'margin_percent',
                'other_fees',
                'pricing_formula',
            ]);
        });
    }
};

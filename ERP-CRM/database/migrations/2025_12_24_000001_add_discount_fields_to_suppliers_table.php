<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('base_discount', 5, 2)->default(0)->after('payment_terms')->comment('Chiết khấu cơ bản (%)');
            $table->decimal('volume_discount', 5, 2)->default(0)->after('base_discount')->comment('Chiết khấu theo số lượng (%)');
            $table->integer('volume_threshold')->default(0)->after('volume_discount')->comment('Ngưỡng số lượng áp dụng CK');
            $table->decimal('early_payment_discount', 5, 2)->default(0)->after('volume_threshold')->comment('Chiết khấu thanh toán sớm (%)');
            $table->integer('early_payment_days')->default(7)->after('early_payment_discount')->comment('Số ngày thanh toán sớm');
            $table->decimal('special_discount', 5, 2)->default(0)->after('early_payment_days')->comment('Chiết khấu đặc biệt (%)');
            $table->text('special_discount_condition')->nullable()->after('special_discount')->comment('Điều kiện CK đặc biệt');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'base_discount', 'volume_discount', 'volume_threshold',
                'early_payment_discount', 'early_payment_days',
                'special_discount', 'special_discount_condition'
            ]);
        });
    }
};

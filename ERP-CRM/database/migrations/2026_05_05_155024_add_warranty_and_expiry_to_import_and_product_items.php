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
        Schema::table('import_items', function (Blueprint $table) {
            $table->integer('warranty_months')->nullable()->after('comments')->comment('Số tháng bảo hành');
            $table->date('expiry_date')->nullable()->after('warranty_months')->comment('Ngày hết hạn');
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->integer('warranty_months')->nullable()->after('status')->comment('Số tháng bảo hành');
            $table->date('expiry_date')->nullable()->after('warranty_months')->comment('Ngày hết hạn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_items', function (Blueprint $table) {
            $table->dropColumn(['warranty_months', 'expiry_date']);
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn(['warranty_months', 'expiry_date']);
        });
    }
};

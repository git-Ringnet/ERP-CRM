<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Một dòng yêu cầu chứa toàn bộ serial của nó (đơn 1000+ license) -> VARCHAR(255) không đủ, dùng MEDIUMTEXT (16MB).
    public function up(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->mediumText('serial_number')->nullable()->change();
        });

        Schema::table('sales_revenues', function (Blueprint $table) {
            $table->mediumText('serial_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->change();
        });

        Schema::table('sales_revenues', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->change();
        });
    }
};

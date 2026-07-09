<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_requests', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });
        Schema::table('sale_order_requests', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->change();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_requests', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });
        Schema::table('sale_order_requests', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable(false)->change();
            $table->foreign('sale_id')->references('id')->on('sales')->cascadeOnDelete();
        });
    }
};

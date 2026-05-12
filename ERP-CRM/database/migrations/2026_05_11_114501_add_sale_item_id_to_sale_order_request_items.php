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
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_item_id')->nullable()->after('sale_order_request_id');
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->dropColumn('sale_item_id');
        });
    }
};

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
        Schema::table('sale_items', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['product_id']);
            }
            $table->unsignedBigInteger('product_id')->nullable()->change();
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['product_id']);
            }
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            }
        });
    }
};

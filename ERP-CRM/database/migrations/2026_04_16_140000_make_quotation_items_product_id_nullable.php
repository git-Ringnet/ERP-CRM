<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['product_id']);
            }
            $table->unsignedBigInteger('product_id')->nullable()->change();
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
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

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
        Schema::table('supplier_price_lists', function (Blueprint $table) {
            // Stores which price column to use as primary for calculations
            // e.g. 'list_price', 'price_1yr', 'custom_Silver', etc.
            $table->string('primary_price_column', 100)->nullable()->after('custom_columns');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_price_lists', function (Blueprint $table) {
            $table->dropColumn('primary_price_column');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add foreign key constraints to product_items after imports/exports tables exist
     */
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->foreign('import_id')->references('id')->on('imports')->onDelete('set null');
            $table->foreign('export_id')->references('id')->on('exports')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropForeign(['import_id']);
            $table->dropForeign(['export_id']);
        });
    }
};

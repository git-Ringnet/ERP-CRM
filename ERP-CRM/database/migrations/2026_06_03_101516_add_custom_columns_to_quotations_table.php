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
        Schema::table('quotations', function (Blueprint $table) {
            $table->json('custom_columns')->nullable()->after('note');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->json('custom_fields')->nullable()->after('total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('custom_columns');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('custom_fields');
        });
    }
};

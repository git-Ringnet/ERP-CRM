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
        Schema::table('export_items', function (Blueprint $table) {
            $table->decimal('unit_price', 18, 2)->after('quantity')->nullable();
            $table->decimal('total', 18, 2)->after('unit_price')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_items', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'total']);
        });
    }
};

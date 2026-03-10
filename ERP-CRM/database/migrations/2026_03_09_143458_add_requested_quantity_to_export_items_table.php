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
            $table->integer('requested_quantity')->nullable()->after('quantity')->comment('Số lượng yêu cầu xuất');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_items', function (Blueprint $table) {
            $table->dropColumn('requested_quantity');
        });
    }
};

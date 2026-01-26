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
        Schema::table('import_items', function (Blueprint $table) {
            $table->decimal('warehouse_price', 15, 2)->default(0)->after('cost')->comment('Giá kho (bao gồm chi phí phục vụ)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_items', function (Blueprint $table) {
            $table->dropColumn('warehouse_price');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_items', function (Blueprint $table) {
            $table->foreignId('warehouse_id')
                ->nullable()
                ->after('product_id')
                ->constrained('warehouses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('export_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};

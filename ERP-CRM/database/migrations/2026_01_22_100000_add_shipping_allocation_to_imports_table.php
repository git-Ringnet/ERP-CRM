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
        Schema::table('imports', function (Blueprint $table) {
            $table->foreignId('shipping_allocation_id')
                ->nullable()
                ->after('reference_id')
                ->constrained('shipping_allocations')
                ->nullOnDelete()
                ->comment('Phân bổ chi phí vận chuyển');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropForeign(['shipping_allocation_id']);
            $table->dropColumn('shipping_allocation_id');
        });
    }
};

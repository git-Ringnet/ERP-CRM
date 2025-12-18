<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Requirements: 1.1, 1.2, 2.1 - Add warranty_start_date to sale_items
     */
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->date('warranty_start_date')->nullable()->after('warranty_months')
                  ->comment('Ngày bắt đầu bảo hành');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('warranty_start_date');
        });
    }
};

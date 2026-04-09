<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('overdue_interest_cost', 15, 2)->nullable()->after('finance_cost_percent')
                ->comment('Lãi vay phát sinh do nợ quá hạn (VND)');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('overdue_interest_cost');
        });
    }
};

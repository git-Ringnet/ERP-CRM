<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('paid_amount_foreign', 18, 4)->default(0)->after('paid_amount');
            $table->decimal('debt_amount_foreign', 18, 4)->default(0)->after('debt_amount');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('paid_amount_foreign', 18, 4)->default(0)->after('paid_amount');
            $table->decimal('debt_amount_foreign', 18, 4)->default(0)->after('debt_amount');
        });

        // Data migration: update existing foreign currency records
        // For VND records, paid_amount_foreign = paid_amount, debt_amount_foreign = debt_amount
        DB::table('sales')->update([
            'paid_amount_foreign' => DB::raw('CASE WHEN exchange_rate > 0 THEN paid_amount / exchange_rate ELSE paid_amount END'),
            'debt_amount_foreign' => DB::raw('CASE WHEN exchange_rate > 0 THEN debt_amount / exchange_rate ELSE debt_amount END'),
        ]);

        DB::table('purchase_orders')->update([
            'paid_amount_foreign' => DB::raw('CASE WHEN exchange_rate > 0 THEN paid_amount / exchange_rate ELSE paid_amount END'),
            'debt_amount_foreign' => DB::raw('CASE WHEN exchange_rate > 0 THEN debt_amount / exchange_rate ELSE debt_amount END'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['paid_amount_foreign', 'debt_amount_foreign']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['paid_amount_foreign', 'debt_amount_foreign']);
        });
    }
};

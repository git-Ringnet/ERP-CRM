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
        Schema::table('financial_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_transactions', 'currency_id')) {
                $table->foreignId('currency_id')->nullable()->after('transaction_category_id')->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('financial_transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 4)->nullable()->after('currency_id');
            }
            if (!Schema::hasColumn('financial_transactions', 'amount_foreign')) {
                $table->decimal('amount_foreign', 15, 2)->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate', 'amount_foreign']);
        });
    }
};

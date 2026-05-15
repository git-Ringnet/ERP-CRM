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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('debt_limit_type')->default('amount')->after('debt_limit');
            $table->decimal('debt_limit_value', 15, 2)->default(0)->after('debt_limit_type');
            $table->json('payment_terms')->nullable()->after('debt_days');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->json('payment_terms')->nullable()->after('payment_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['debt_limit_type', 'debt_limit_value', 'payment_terms']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_terms']);
        });
    }
};

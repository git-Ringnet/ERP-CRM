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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'payment_term_type')) {
                $table->string('payment_term_type')->nullable();
            }
            if (!Schema::hasColumn('sales', 'is_payment_exception')) {
                $table->boolean('is_payment_exception')->default(0);
            }
            if (!Schema::hasColumn('sales', 'payment_exception_file')) {
                $table->string('payment_exception_file')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_term_type', 'is_payment_exception', 'payment_exception_file']);
        });
    }
};

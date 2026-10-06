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
        if (Schema::hasTable('sale_payment_schedules') && !Schema::hasColumn('sale_payment_schedules', 'payment_type')) {
            Schema::table('sale_payment_schedules', function (Blueprint $table) {
                $table->string('payment_type', 50)->default('official')->after('status')->comment('official (Chính thức) or unofficial (Chưa chính thức)');
            });
        }

        if (Schema::hasTable('payment_histories') && !Schema::hasColumn('payment_histories', 'payment_type')) {
            Schema::table('payment_histories', function (Blueprint $table) {
                $table->string('payment_type', 50)->default('official')->after('payment_method')->comment('official or unofficial');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sale_payment_schedules') && Schema::hasColumn('sale_payment_schedules', 'payment_type')) {
            Schema::table('sale_payment_schedules', function (Blueprint $table) {
                $table->dropColumn('payment_type');
            });
        }

        if (Schema::hasTable('payment_histories') && Schema::hasColumn('payment_histories', 'payment_type')) {
            Schema::table('payment_histories', function (Blueprint $table) {
                $table->dropColumn('payment_type');
            });
        }
    }
};

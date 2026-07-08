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
            $table->unsignedBigInteger('payment_exception_delegated_to')->nullable();
            $table->foreign('payment_exception_delegated_to')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('sale_payment_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('delegated_to_id')->nullable();
            $table->foreign('delegated_to_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_payment_schedules', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['delegated_to_id']);
            }
            $table->dropColumn('delegated_to_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['payment_exception_delegated_to']);
            }
            $table->dropColumn('payment_exception_delegated_to');
        });
    }
};

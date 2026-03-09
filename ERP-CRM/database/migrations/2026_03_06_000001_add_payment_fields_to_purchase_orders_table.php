<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total');
            $table->decimal('debt_amount', 15, 2)->default(0)->after('paid_amount');
            $table->string('payment_status', 20)->default('unpaid')->after('debt_amount');
        });

        // Backfill: set debt_amount = total for all non-cancelled POs
        DB::statement("UPDATE purchase_orders SET debt_amount = total WHERE status != 'cancelled'");
        DB::statement("UPDATE purchase_orders SET debt_amount = 0 WHERE status = 'cancelled'");
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'debt_amount', 'payment_status']);
        });
    }
};

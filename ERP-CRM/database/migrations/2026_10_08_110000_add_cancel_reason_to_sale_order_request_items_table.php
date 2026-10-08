<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_order_request_items', 'cancel_reason')) {
                $table->string('cancel_reason', 500)->nullable()->after('is_cancelled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_order_request_items', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
        });
    }
};

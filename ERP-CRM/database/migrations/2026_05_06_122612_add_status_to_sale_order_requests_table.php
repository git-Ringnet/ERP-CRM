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
        Schema::table('sale_order_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_order_requests', 'status')) {
                $table->string('status', 20)->default('submitted')->after('sent_at');
            }
            if (!Schema::hasColumn('sale_order_requests', 'rejection_note')) {
                $table->text('rejection_note')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_requests', function (Blueprint $table) {
            if (Schema::hasColumn('sale_order_requests', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('sale_order_requests', 'rejection_note')) {
                $table->dropColumn('rejection_note');
            }
        });
    }
};

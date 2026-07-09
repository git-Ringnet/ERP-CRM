<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_requests', function (Blueprint $table) {
            $table->string('source_type')->default('sale_order')->after('sale_id');
            $table->foreignId('ticket_id')->nullable()->after('source_type')->constrained('tickets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_order_requests', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropColumn(['source_type', 'ticket_id']);
        });
    }
};

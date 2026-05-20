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
            $table->softDeletes();
            $table->string('delete_reason')->nullable()->after('rejection_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_order_requests', function (Blueprint $table) {
            $table->dropColumn('delete_reason');
            $table->dropSoftDeletes();
        });
    }
};

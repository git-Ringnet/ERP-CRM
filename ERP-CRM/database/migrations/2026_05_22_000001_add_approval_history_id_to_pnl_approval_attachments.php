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
        Schema::table('pnl_approval_attachments', function (Blueprint $table) {
            $table->foreignId('approval_history_id')
                ->after('sale_id')
                ->nullable()
                ->constrained('approval_histories')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pnl_approval_attachments', function (Blueprint $table) {
            $table->dropForeign(['approval_history_id']);
            $table->dropColumn('approval_history_id');
        });
    }
};

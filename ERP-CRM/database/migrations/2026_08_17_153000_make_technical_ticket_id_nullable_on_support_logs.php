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
        Schema::table('technical_support_logs', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['technical_ticket_id']);
            
            // Make the column nullable
            $table->unsignedBigInteger('technical_ticket_id')->nullable()->change();
            
            // Re-add foreign key with nullOnDelete
            $table->foreign('technical_ticket_id')
                  ->references('id')
                  ->on('technical_tickets')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_support_logs', function (Blueprint $table) {
            $table->dropForeign(['technical_ticket_id']);
            
            // Re-vert to non-nullable (will fail if null values exist, but standard rollback)
            $table->unsignedBigInteger('technical_ticket_id')->change();
            
            $table->foreign('technical_ticket_id')
                  ->references('id')
                  ->on('technical_tickets')
                  ->onDelete('cascade');
        });
    }
};

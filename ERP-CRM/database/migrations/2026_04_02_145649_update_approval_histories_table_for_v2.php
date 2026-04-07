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
        Schema::table('approval_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('original_approver_id')->nullable()->after('approver_name');
            $table->unsignedBigInteger('delegated_to_id')->nullable()->after('original_approver_id');
            
            // Re-define action column to include 'skipped' and 'delegated'
            $table->string('action')->change(); 
        });

        // Add foreign keys separately to be safe
        Schema::table('approval_histories', function (Blueprint $table) {
            $table->foreign('original_approver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('delegated_to_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_histories', function (Blueprint $table) {
            $table->dropForeign(['original_approver_id']);
            $table->dropForeign(['delegated_to_id']);
            $table->dropColumn(['original_approver_id', 'delegated_to_id']);
            
            // Revert action is harder in change() but we can leave it as string for safety
        });
    }
};

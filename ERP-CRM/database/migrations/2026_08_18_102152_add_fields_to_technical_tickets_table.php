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
        Schema::table('technical_tickets', function (Blueprint $table) {
            $table->foreignId('sales_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('department')->nullable();
            $table->string('project_name')->nullable();
            $table->text('solution')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_tickets', function (Blueprint $table) {
            $table->dropForeign(['sales_owner_id']);
            $table->dropForeign(['team_lead_id']);
            $table->dropColumn(['sales_owner_id', 'team_lead_id', 'department', 'project_name', 'solution']);
        });
    }
};

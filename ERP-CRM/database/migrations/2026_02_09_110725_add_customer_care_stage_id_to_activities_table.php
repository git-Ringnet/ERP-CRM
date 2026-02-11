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
        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('customer_care_stage_id')->nullable()->after('lead_id')->constrained()->onDelete('cascade');
            $table->index('customer_care_stage_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['customer_care_stage_id']);
            $table->dropIndex(['customer_care_stage_id']);
            $table->dropColumn('customer_care_stage_id');
        });
    }
};

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
        Schema::table('sales', function (Blueprint $blueprint) {
            $blueprint->string('pl_status')->default('draft')->after('status');
            $blueprint->timestamp('pl_approved_at')->nullable()->after('pl_status');
            $blueprint->unsignedBigInteger('pl_approved_by')->nullable()->after('pl_approved_at');
            
            $blueprint->foreign('pl_approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['pl_approved_by']);
            $blueprint->dropColumn(['pl_status', 'pl_approved_at', 'pl_approved_by']);
        });
    }
};

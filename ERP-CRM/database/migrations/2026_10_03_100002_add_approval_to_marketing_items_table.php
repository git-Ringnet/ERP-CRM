<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_items', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_items', 'approval_status')) {
                $table->string('approval_status')->default('approved')->after('status'); // pending, approved, rejected
            }
            if (!Schema::hasColumn('marketing_items', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete()->after('approval_status');
            }
            if (!Schema::hasColumn('marketing_items', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('submitted_by');
            }
            if (!Schema::hasColumn('marketing_items', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('marketing_items', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_items', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'approval_status',
                'submitted_by',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};

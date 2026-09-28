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
        Schema::table('technical_ticket_engineers', function (Blueprint $table) {
            if (!Schema::hasColumn('technical_ticket_engineers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('user_id');
            }
            if (!Schema::hasColumn('technical_ticket_engineers', 'handed_over_at')) {
                $table->timestamp('handed_over_at')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('technical_ticket_engineers', 'handed_over_by')) {
                $table->foreignId('handed_over_by')->nullable()->after('handed_over_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('technical_ticket_engineers', 'handover_note')) {
                $table->text('handover_note')->nullable()->after('handed_over_by');
            }
        });

        Schema::table('technical_ticket_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('technical_ticket_attachments', 'is_initial')) {
                $table->boolean('is_initial')->default(false)->after('document_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technical_ticket_engineers', function (Blueprint $table) {
            $table->dropForeign(['handed_over_by']);
            $table->dropColumn(['is_active', 'handed_over_at', 'handed_over_by', 'handover_note']);
        });

        Schema::table('technical_ticket_attachments', function (Blueprint $table) {
            $table->dropColumn('is_initial');
        });
    }
};

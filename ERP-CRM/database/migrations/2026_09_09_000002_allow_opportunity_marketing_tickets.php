<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Marketing ticket may originate from a Sales opportunity (for example a
     * BOD-approved giveaway), not only from a formal Marketing event.
     */
    public function up(): void
    {
        Schema::table('marketing_tickets', function (Blueprint $table) {
            $table->dropForeign(['marketing_event_id']);
            $table->foreignId('marketing_event_id')->nullable()->change();
            $table->foreign('marketing_event_id')->references('id')->on('marketing_events')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->after('marketing_event_id')
                ->constrained('opportunities')->nullOnDelete();
        });

        Schema::table('marketing_requests', function (Blueprint $table) {
            $table->dropForeign(['marketing_event_id']);
            $table->foreignId('marketing_event_id')->nullable()->change();
            $table->foreign('marketing_event_id')->references('id')->on('marketing_events')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->after('marketing_event_id')
                ->constrained('opportunities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_requests', function (Blueprint $table) {
            $table->dropForeign(['opportunity_id']);
            $table->dropColumn('opportunity_id');
        });

        Schema::table('marketing_tickets', function (Blueprint $table) {
            $table->dropForeign(['opportunity_id']);
            $table->dropColumn('opportunity_id');
        });
    }
};

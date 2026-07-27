<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_events', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->string('code')->nullable()->unique()->after('id');
            $table->enum('scope', ['internal', 'external'])->default('external')->after('code');
            $table->foreignId('vendor_id')->nullable()->constrained('suppliers')->onDelete('set null')->after('location');
            $table->text('vendor_other_note')->nullable()->after('vendor_id');
            $table->enum('partner_cooperation', ['yes', 'no', 'other'])->default('no')->after('vendor_other_note');
            $table->text('partner_info')->nullable()->after('partner_cooperation');
            $table->enum('organize_type', ['workshop', 'networking_dinner', 'exhibition', 'other'])->default('workshop')->after('partner_info');
            $table->string('organize_type_other')->nullable()->after('organize_type');
            $table->time('start_time')->nullable()->after('event_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->integer('target_audience_count')->default(0)->after('end_time');
            $table->text('target_audience_note')->nullable()->after('target_audience_count');
            $table->text('budget_external_note')->nullable()->after('actual_cost');
            $table->string('funding_source')->nullable()->after('budget_external_note');
            $table->text('special_notes')->nullable()->after('funding_source');
            $table->json('attachments')->nullable()->after('special_notes');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_events', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->dropForeign(['vendor_id']);
            $table->dropColumn([
                'code', 'scope', 'vendor_id', 'vendor_other_note', 'partner_cooperation',
                'partner_info', 'organize_type', 'organize_type_other', 'start_time', 'end_time',
                'target_audience_count', 'target_audience_note', 'budget_external_note', 'funding_source',
                'special_notes', 'attachments'
            ]);
        });
    }
};

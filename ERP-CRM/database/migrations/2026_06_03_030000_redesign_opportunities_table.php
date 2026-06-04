<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Redesign opportunities table:
     * Từ Sales Pipeline (cũ) → Báo cáo hoạt động tiếp cận khách hàng (mới)
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            // Drop old columns (nếu tồn tại)
            if (Schema::hasColumn('opportunities', 'amount')) {
                $table->dropColumn(['amount']);
            }
            if (Schema::hasColumn('opportunities', 'currency')) {
                $table->dropColumn(['currency']);
            }
            if (Schema::hasColumn('opportunities', 'probability')) {
                $table->dropColumn(['probability']);
            }
            if (Schema::hasColumn('opportunities', 'stage')) {
                $table->dropColumn(['stage']);
            }
            if (Schema::hasColumn('opportunities', 'expected_close_date')) {
                $table->dropColumn(['expected_close_date']);
            }
            if (Schema::hasColumn('opportunities', 'closed_at')) {
                $table->dropColumn(['closed_at']);
            }
            if (Schema::hasColumn('opportunities', 'next_action_date')) {
                $table->dropColumn(['next_action_date']);
            }
        });

        Schema::table('opportunities', function (Blueprint $table) {
            // Thay đổi customer_id thành nullable (vì EU không có customer record)
            $table->unsignedBigInteger('customer_id')->nullable()->change();

            // ===== Customer Info =====
            $table->string('customer_type', 10)->default('si')->after('id'); // si | eu
            $table->foreignId('contact_id')->nullable()->after('customer_id')->constrained('contacts')->nullOnDelete();
            $table->string('eu_company_name')->nullable()->after('contact_id');
            $table->string('eu_contact_name')->nullable()->after('eu_company_name');
            $table->string('eu_phone', 50)->nullable()->after('eu_contact_name');
            $table->string('eu_email')->nullable()->after('eu_phone');
            $table->string('eu_position')->nullable()->after('eu_email');

            // ===== Activity Info =====
            $table->string('activity_type', 50)->default('other')->after('name');
            $table->string('activity_type_other')->nullable()->after('activity_type');
            $table->date('activity_date')->nullable()->after('activity_type_other');
            $table->time('start_time')->nullable()->after('activity_date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->integer('duration_minutes')->default(0)->after('end_time');
            $table->text('notes')->nullable()->after('description');
            $table->text('materials_required')->nullable()->after('notes');
            $table->text('giveaway')->nullable()->after('materials_required');

            // ===== Status =====
            $table->string('status', 20)->default('planned')->after('giveaway');
            $table->text('cancel_reason')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('cancel_reason');

            // ===== Technical =====
            $table->boolean('needs_technical')->default(false)->after('completed_at');
            $table->foreignId('technical_user_id')->nullable()->after('needs_technical')->constrained('users')->nullOnDelete();

            // ===== Report (Phase 2) =====
            $table->text('customer_feedback')->nullable()->after('technical_user_id');
            $table->text('meeting_result')->nullable()->after('customer_feedback');
            $table->text('pain_points')->nullable()->after('meeting_result');
            // next_action đã tồn tại từ migration cũ
            $table->string('potential_rating', 10)->nullable()->after('next_action');

            // ===== Tracking =====
            $table->foreignId('assigned_to')->nullable()->change();
            $table->foreignId('project_id')->nullable()->after('potential_rating')->constrained('projects')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['technical_user_id']);
            $table->dropForeign(['project_id']);

            $table->dropColumn([
                'customer_type', 'contact_id',
                'eu_company_name', 'eu_contact_name', 'eu_phone', 'eu_email', 'eu_position',
                'activity_type', 'activity_type_other', 'activity_date', 'start_time', 'end_time', 'duration_minutes',
                'notes', 'materials_required', 'giveaway',
                'status', 'cancel_reason', 'completed_at',
                'needs_technical', 'technical_user_id',
                'customer_feedback', 'meeting_result', 'pain_points', 'potential_rating',
                'project_id',
            ]);

            // Restore old columns
            $table->decimal('amount', 15, 2)->default(0)->after('name');
            $table->string('stage')->default('new')->after('amount');
            $table->integer('probability')->default(0)->after('stage');
            $table->date('expected_close_date')->nullable()->after('probability');
            $table->date('closed_at')->nullable()->after('expected_close_date');
            $table->date('next_action_date')->nullable()->after('description');
        });
    }
};

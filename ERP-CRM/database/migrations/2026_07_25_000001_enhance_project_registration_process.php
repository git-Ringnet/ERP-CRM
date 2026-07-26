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
        // 1. Add enhancement columns to projects table
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'name_en')) {
                $table->string('name_en')->nullable()->after('name')->comment('Tên dự án tiếng Anh chuẩn gửi Hãng');
            }
            if (!Schema::hasColumn('projects', 'assigned_team')) {
                $table->enum('assigned_team', ['po_team', 'pm_team'])->default('pm_team')->after('distributor_am')->comment('Team chịu trách nhiệm xử lý (PO cho FTN, PM cho Non-FTN)');
            }
            
            // Special request & Fortinet trade-up fields
            if (!Schema::hasColumn('projects', 'special_request_type')) {
                $table->string('special_request_type', 100)->nullable()->after('note')->comment('Yêu cầu thêm: bom_project, urgent_price');
            }
            if (!Schema::hasColumn('projects', 'special_request_note')) {
                $table->text('special_request_note')->nullable()->after('special_request_type')->comment('Ghi chú bắt buộc cho yêu cầu thêm');
            }
            if (!Schema::hasColumn('projects', 'sn_numbers')) {
                $table->text('sn_numbers')->nullable()->after('deal_type')->comment('Danh sách S/N khi chọn Fortinet Trade up');
            }
            
            // Initial 4-hour SLA intake fields
            if (!Schema::hasColumn('projects', 'initial_sla_due_at')) {
                $table->timestamp('initial_sla_due_at')->nullable()->after('sn_numbers')->comment('Hạn 4 giờ tiếp nhận ban đầu');
            }
            if (!Schema::hasColumn('projects', 'initial_processed_at')) {
                $table->timestamp('initial_processed_at')->nullable()->after('initial_sla_due_at')->comment('Thời điểm bấm tiếp nhận');
            }
            if (!Schema::hasColumn('projects', 'initial_processed_by')) {
                $table->foreignId('initial_processed_by')->nullable()->constrained('users')->nullOnDelete()->after('initial_processed_at');
            }
            if (!Schema::hasColumn('projects', 'intake_status')) {
                $table->enum('intake_status', ['pending', 'registered', 'duplicate', 'incomplete'])->default('pending')->after('initial_processed_by');
            }
            if (!Schema::hasColumn('projects', 'intake_note')) {
                $table->text('intake_note')->nullable()->after('intake_status');
            }
            if (!Schema::hasColumn('projects', 'duplicate_sales_info')) {
                $table->text('duplicate_sales_info')->nullable()->after('intake_note');
            }
            
            // Overall Workflow registration status
            if (!Schema::hasColumn('projects', 'registration_status')) {
                $table->string('registration_status', 50)->default('submitted')->after('intake_status')
                      ->comment('submitted, processing, vendor_processing, vendor_reminded, vendor_quoted, vendor_rejected, update_status, closed_won, closed_lost, cancelled, expired, on_hold');
            }
            
            // Vendor SLA (3 Working Days) fields
            if (!Schema::hasColumn('projects', 'vendor_submitted_at')) {
                $table->timestamp('vendor_submitted_at')->nullable()->after('registration_status');
            }
            if (!Schema::hasColumn('projects', 'vendor_due_at')) {
                $table->timestamp('vendor_due_at')->nullable()->after('vendor_submitted_at')->comment('Hạn Hãng phản hồi (3 ngày làm việc)');
            }
            if (!Schema::hasColumn('projects', 'vendor_reminder_count')) {
                $table->integer('vendor_reminder_count')->default(0)->after('vendor_due_at');
            }
            if (!Schema::hasColumn('projects', 'last_vendor_reminded_at')) {
                $table->timestamp('last_vendor_reminded_at')->nullable()->after('vendor_reminder_count');
            }
            if (!Schema::hasColumn('projects', 'vendor_deal_id')) {
                $table->string('vendor_deal_id', 100)->nullable()->after('last_vendor_reminded_at');
            }
            if (!Schema::hasColumn('projects', 'vendor_quote_file')) {
                $table->text('vendor_quote_file')->nullable()->after('vendor_deal_id');
            }
            if (!Schema::hasColumn('projects', 'vendor_quote_note')) {
                $table->text('vendor_quote_note')->nullable()->after('vendor_quote_file');
            }
            if (!Schema::hasColumn('projects', 'vendor_quote_valid_until')) {
                $table->date('vendor_quote_valid_until')->nullable()->after('vendor_quote_note');
            }
            
            // Monthly Sales Status Update fields
            if (!Schema::hasColumn('projects', 'forecast_stage')) {
                $table->enum('forecast_stage', ['commit', 'best_case', 'close_deal'])->nullable()->after('vendor_quote_valid_until');
            }
            if (!Schema::hasColumn('projects', 'support_request_type')) {
                $table->string('support_request_type', 100)->nullable()->after('forecast_stage');
            }
            if (!Schema::hasColumn('projects', 'support_request_note')) {
                $table->text('support_request_note')->nullable()->after('support_request_type');
            }
            if (!Schema::hasColumn('projects', 'last_sales_updated_at')) {
                $table->timestamp('last_sales_updated_at')->nullable()->after('support_request_note');
            }
            if (!Schema::hasColumn('projects', 'last_sales_reminded_at')) {
                $table->timestamp('last_sales_reminded_at')->nullable()->after('last_sales_updated_at');
            }
            if (!Schema::hasColumn('projects', 'sales_reminder_count')) {
                $table->integer('sales_reminder_count')->default(0)->after('last_sales_reminded_at');
            }
            if (!Schema::hasColumn('projects', 'missed_update_count')) {
                $table->integer('missed_update_count')->default(0)->after('sales_reminder_count');
            }
            
            // Close Deal & Auto PO Match fields
            if (!Schema::hasColumn('projects', 'close_reason')) {
                $table->string('close_reason', 150)->nullable()->after('missed_update_count');
            }
            if (!Schema::hasColumn('projects', 'close_note')) {
                $table->text('close_note')->nullable()->after('close_reason');
            }
            if (!Schema::hasColumn('projects', 'po_code')) {
                $table->string('po_code', 100)->nullable()->after('close_note');
            }
            if (!Schema::hasColumn('projects', 'order_value')) {
                $table->decimal('order_value', 18, 2)->nullable()->after('po_code');
            }
            if (!Schema::hasColumn('projects', 'order_date')) {
                $table->date('order_date')->nullable()->after('order_value');
            }
        });

        // 2. Table project_vendor_quotes (Quotation Versioning v1, v2, v3...)
        if (!Schema::hasTable('project_vendor_quotes')) {
            Schema::create('project_vendor_quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->integer('version_number')->default(1);
                $table->string('vendor_deal_id', 100)->nullable();
                $table->text('quote_file')->nullable();
                $table->text('quote_note')->nullable();
                $table->date('valid_until')->nullable();
                $table->text('requote_reason')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Table project_registration_notes (Interactive Discussion Notes & SLA Extension)
        if (!Schema::hasTable('project_registration_notes')) {
            Schema::create('project_registration_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('user_role', ['pm', 'sales', 'po'])->default('sales');
                $table->text('content');
                $table->text('attachments')->nullable();
                $table->integer('sla_extended_days')->default(0);
                $table->timestamps();
            });
        }

        // 4. Table project_status_updates (Monthly Update History)
        if (!Schema::hasTable('project_status_updates')) {
            Schema::create('project_status_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('forecast_stage', ['commit', 'best_case', 'close_deal']);
                $table->string('support_request_type', 100)->nullable();
                $table->text('support_request_note')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_status_updates');
        Schema::dropIfExists('project_registration_notes');
        Schema::dropIfExists('project_vendor_quotes');

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'initial_processed_by')) {
                $table->dropForeign(['initial_processed_by']);
            }

            $columnsToDrop = [
                'name_en',
                'assigned_team',
                'special_request_type',
                'special_request_note',
                'sn_numbers',
                'initial_sla_due_at',
                'initial_processed_at',
                'initial_processed_by',
                'intake_status',
                'intake_note',
                'duplicate_sales_info',
                'registration_status',
                'vendor_submitted_at',
                'vendor_due_at',
                'vendor_reminder_count',
                'last_vendor_reminded_at',
                'vendor_deal_id',
                'vendor_quote_file',
                'vendor_quote_note',
                'vendor_quote_valid_until',
                'forecast_stage',
                'support_request_type',
                'support_request_note',
                'last_sales_updated_at',
                'last_sales_reminded_at',
                'sales_reminder_count',
                'missed_update_count',
                'close_reason',
                'close_note',
                'po_code',
                'order_value',
                'order_date',
            ];

            $existingColumns = [];
            foreach ($columnsToDrop as $col) {
                if (Schema::hasColumn('projects', $col)) {
                    $existingColumns[] = $col;
                }
            }
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};

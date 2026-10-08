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
        // 1. Projects: Thêm người phụ trách thứ 2 (P.I.C phụ)
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'secondary_manager_id')) {
                $table->foreignId('secondary_manager_id')
                    ->nullable()
                    ->after('manager_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        // 2. Sales: Thêm người phụ trách thứ 2 và tỷ lệ phân bổ margin
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'secondary_user_id')) {
                $table->foreignId('secondary_user_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('sales', 'margin_beneficiary_id')) {
                $table->foreignId('margin_beneficiary_id')
                    ->nullable()
                    ->after('secondary_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('sales', 'primary_margin_percent')) {
                $table->decimal('primary_margin_percent', 5, 2)
                    ->default(100.00)
                    ->after('margin_beneficiary_id');
            }
            if (!Schema::hasColumn('sales', 'secondary_margin_percent')) {
                $table->decimal('secondary_margin_percent', 5, 2)
                    ->default(0.00)
                    ->after('primary_margin_percent');
            }
        });

        // 3. Invoice Requests: Lưu thông tin phân bổ margin tại bước XHĐ
        Schema::table('invoice_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_requests', 'secondary_user_id')) {
                $table->foreignId('secondary_user_id')
                    ->nullable()
                    ->after('requester_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('invoice_requests', 'margin_beneficiary_id')) {
                $table->foreignId('margin_beneficiary_id')
                    ->nullable()
                    ->after('secondary_user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('invoice_requests', 'primary_margin_percent')) {
                $table->decimal('primary_margin_percent', 5, 2)
                    ->default(100.00)
                    ->after('margin_beneficiary_id');
            }
            if (!Schema::hasColumn('invoice_requests', 'secondary_margin_percent')) {
                $table->decimal('secondary_margin_percent', 5, 2)
                    ->default(0.00)
                    ->after('primary_margin_percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_requests', 'secondary_margin_percent')) {
                $table->dropColumn('secondary_margin_percent');
            }
            if (Schema::hasColumn('invoice_requests', 'primary_margin_percent')) {
                $table->dropColumn('primary_margin_percent');
            }
            if (Schema::hasColumn('invoice_requests', 'margin_beneficiary_id')) {
                $table->dropForeign(['margin_beneficiary_id']);
                $table->dropColumn('margin_beneficiary_id');
            }
            if (Schema::hasColumn('invoice_requests', 'secondary_user_id')) {
                $table->dropForeign(['secondary_user_id']);
                $table->dropColumn('secondary_user_id');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'secondary_margin_percent')) {
                $table->dropColumn('secondary_margin_percent');
            }
            if (Schema::hasColumn('sales', 'primary_margin_percent')) {
                $table->dropColumn('primary_margin_percent');
            }
            if (Schema::hasColumn('sales', 'margin_beneficiary_id')) {
                $table->dropForeign(['margin_beneficiary_id']);
                $table->dropColumn('margin_beneficiary_id');
            }
            if (Schema::hasColumn('sales', 'secondary_user_id')) {
                $table->dropForeign(['secondary_user_id']);
                $table->dropColumn('secondary_user_id');
            }
        });

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'secondary_manager_id')) {
                $table->dropForeign(['secondary_manager_id']);
                $table->dropColumn('secondary_manager_id');
            }
        });
    }
};

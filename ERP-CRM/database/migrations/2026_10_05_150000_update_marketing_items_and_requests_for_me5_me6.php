<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_items', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_items', 'funding_source')) {
                $table->string('funding_source')->nullable()->after('unit_cost');
            }
            if (!Schema::hasColumn('marketing_items', 'marketing_supplier_fund_id')) {
                $table->foreignId('marketing_supplier_fund_id')->nullable()->constrained('marketing_supplier_funds')->nullOnDelete()->after('funding_source');
            }
            if (!Schema::hasColumn('marketing_items', 'purpose')) {
                $table->text('purpose')->nullable()->after('description');
            }
            if (!Schema::hasColumn('marketing_items', 'marketing_event_id')) {
                $table->foreignId('marketing_event_id')->nullable()->constrained('marketing_events')->nullOnDelete()->after('purpose');
            }
            if (!Schema::hasColumn('marketing_items', 'marketing_request_id')) {
                $table->foreignId('marketing_request_id')->nullable()->constrained('marketing_requests')->nullOnDelete()->after('marketing_event_id');
            }
            if (!Schema::hasColumn('marketing_items', 'total_estimated_cost')) {
                $table->decimal('total_estimated_cost', 15, 2)->default(0)->after('unit_cost');
            }
        });

        Schema::table('marketing_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_requests', 'funding_allocations')) {
                $table->json('funding_allocations')->nullable()->after('marketing_supplier_fund_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketing_items', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_items', 'marketing_supplier_fund_id')) {
                $table->dropForeign(['marketing_supplier_fund_id']);
            }
            if (Schema::hasColumn('marketing_items', 'marketing_event_id')) {
                $table->dropForeign(['marketing_event_id']);
            }
            if (Schema::hasColumn('marketing_items', 'marketing_request_id')) {
                $table->dropForeign(['marketing_request_id']);
            }
            $table->dropColumn([
                'funding_source',
                'marketing_supplier_fund_id',
                'purpose',
                'marketing_event_id',
                'marketing_request_id',
                'total_estimated_cost',
            ]);
        });

        Schema::table('marketing_requests', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_requests', 'funding_allocations')) {
                $table->dropColumn('funding_allocations');
            }
        });
    }
};

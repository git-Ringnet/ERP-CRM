<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table for multiple vendors / suppliers
        if (!Schema::hasTable('marketing_event_suppliers')) {
            Schema::create('marketing_event_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketing_event_id')->constrained('marketing_events')->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
            });
        }

        // 2. Add completion, variance, funding sources and internal event columns to marketing_events
        Schema::table('marketing_events', function (Blueprint $table) {
            if (!Schema::hasColumn('marketing_events', 'funding_sources')) {
                $table->json('funding_sources')->nullable()->after('funding_source');
            }
            if (!Schema::hasColumn('marketing_events', 'actual_funding_sources')) {
                $table->json('actual_funding_sources')->nullable()->after('funding_sources');
            }
            if (!Schema::hasColumn('marketing_events', 'variance_amount')) {
                $table->decimal('variance_amount', 15, 2)->nullable()->default(0)->after('actual_cost');
            }
            if (!Schema::hasColumn('marketing_events', 'variance_funding_source')) {
                $table->string('variance_funding_source')->nullable()->after('variance_amount');
            }
            if (!Schema::hasColumn('marketing_events', 'completion_note')) {
                $table->text('completion_note')->nullable()->after('variance_funding_source');
            }
            if (!Schema::hasColumn('marketing_events', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('completion_note');
            }
            if (!Schema::hasColumn('marketing_events', 'completed_by')) {
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete()->after('completed_at');
            }
            if (!Schema::hasColumn('marketing_events', 'internal_department')) {
                $table->string('internal_department')->nullable()->after('scope');
            }
            if (!Schema::hasColumn('marketing_events', 'internal_purpose')) {
                $table->string('internal_purpose')->nullable()->after('internal_department');
            }
        });

        // 3. Update status enum to include 'completed'
        try {
            DB::statement("ALTER TABLE marketing_events MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'draft'");
        } catch (\Throwable $e) {
            // In case DB is SQLite or doesn't support raw ALTER enum
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_event_suppliers');

        Schema::table('marketing_events', function (Blueprint $table) {
            $table->dropForeign(['completed_by']);
            $table->dropColumn([
                'funding_sources',
                'actual_funding_sources',
                'variance_amount',
                'variance_funding_source',
                'completion_note',
                'completed_at',
                'completed_by',
                'internal_department',
                'internal_purpose',
            ]);
        });

        try {
            DB::statement("ALTER TABLE marketing_events MODIFY COLUMN status ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled') DEFAULT 'draft'");
        } catch (\Throwable $e) {
        }
    }
};

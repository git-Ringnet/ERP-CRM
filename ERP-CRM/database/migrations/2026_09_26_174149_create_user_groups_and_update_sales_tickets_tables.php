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
        if (!Schema::hasTable('user_groups')) {
            Schema::create('user_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('leader_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('department')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_group_members')) {
            Schema::create('user_group_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_group_id')->constrained('user_groups')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role')->default('member');
                $table->timestamps();
                $table->unique(['user_group_id', 'user_id']);
            });
        }

        Schema::table('invoice_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_requests', 'needs_draft')) {
                $table->boolean('needs_draft')->default(false)->after('status');
            }
            if (!Schema::hasColumn('invoice_requests', 'is_invoiced')) {
                $table->boolean('is_invoiced')->default(false)->after('needs_draft');
            }
            if (!Schema::hasColumn('invoice_requests', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('is_invoiced');
            }
            if (!Schema::hasColumn('invoice_requests', 'invoiced_at')) {
                $table->dateTime('invoiced_at')->nullable()->after('invoice_number');
            }
        });

        Schema::table('technical_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('technical_tickets', 'user_group_id')) {
                $table->foreignId('user_group_id')->nullable()->after('team_lead_id')->constrained('user_groups')->nullOnDelete();
            }
            if (!Schema::hasColumn('technical_tickets', 'co_lead_ids')) {
                $table->json('co_lead_ids')->nullable()->after('user_group_id');
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'is_from_stock')) {
                $table->boolean('is_from_stock')->default(false)->after('is_service');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            if (Schema::hasColumn('sale_items', 'is_from_stock')) {
                $table->dropColumn('is_from_stock');
            }
        });

        Schema::table('technical_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('technical_tickets', 'co_lead_ids')) {
                $table->dropColumn('co_lead_ids');
            }
            if (Schema::hasColumn('technical_tickets', 'user_group_id')) {
                $table->dropForeign(['user_group_id']);
                $table->dropColumn('user_group_id');
            }
        });

        Schema::table('invoice_requests', function (Blueprint $table) {
            $cols = [];
            foreach (['needs_draft', 'is_invoiced', 'invoice_number', 'invoiced_at'] as $col) {
                if (Schema::hasColumn('invoice_requests', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::dropIfExists('user_group_members');
        Schema::dropIfExists('user_groups');
    }
};

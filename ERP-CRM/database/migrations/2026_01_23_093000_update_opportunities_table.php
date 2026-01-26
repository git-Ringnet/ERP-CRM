<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            if (!Schema::hasColumn('opportunities', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete()->after('customer_id');
            }
            if (!Schema::hasColumn('opportunities', 'currency')) {
                $table->string('currency', 3)->default('VND')->after('amount');
            }
            if (!Schema::hasColumn('opportunities', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->after('description');
            }
            if (!Schema::hasColumn('opportunities', 'closed_at')) {
                $table->date('closed_at')->nullable()->after('expected_close_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn(['lead_id', 'currency', 'assigned_to', 'closed_at']);
        });
    }
};

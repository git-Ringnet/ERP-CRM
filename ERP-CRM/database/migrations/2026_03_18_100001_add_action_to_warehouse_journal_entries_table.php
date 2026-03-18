<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_journal_entries', function (Blueprint $table) {
            $table->string('action', 20)->default('create')->after('reference_code'); // create, update, approve, reject, delete
            $table->string('status', 20)->nullable()->after('action'); // pending, completed, rejected, deleted
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_journal_entries', function (Blueprint $table) {
            $table->dropColumn(['action', 'status']);
        });
    }
};

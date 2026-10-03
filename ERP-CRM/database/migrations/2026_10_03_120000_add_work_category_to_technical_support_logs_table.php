<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('technical_support_logs', function (Blueprint $table) {
            $table->string('work_category', 30)->default('regular')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('technical_support_logs', function (Blueprint $table) {
            $table->dropColumn('work_category');
        });
    }
};

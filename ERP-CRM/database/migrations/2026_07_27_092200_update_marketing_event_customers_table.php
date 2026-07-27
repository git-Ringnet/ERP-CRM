<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_event_customers', function (Blueprint $table) {
            $table->string('status')->default('not_contacted')->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_event_customers', function (Blueprint $table) {
            $table->enum('status', ['invited', 'attended', 'cancelled'])->default('invited')->change();
        });
    }
};

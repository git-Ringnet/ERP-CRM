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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('abv_name')->nullable()->after('name');
            $table->string('am')->nullable()->after('note');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('title')->nullable()->after('last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['abv_name', 'am']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'title']);
        });
    }
};

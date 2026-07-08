<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned()->nullable()->change();
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->bigInteger('user_id')->unsigned()->nullable(false)->change();
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }
};

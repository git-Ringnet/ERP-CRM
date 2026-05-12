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
        Schema::table('import_items', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('comments')->comment('Thời điểm đã cộng vào tồn kho');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_items', function (Blueprint $table) {
            $table->dropColumn('processed_at');
        });
    }
};

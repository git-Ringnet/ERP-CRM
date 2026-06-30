<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->boolean('needs_cq')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->dropColumn('needs_cq');
        });
    }
};

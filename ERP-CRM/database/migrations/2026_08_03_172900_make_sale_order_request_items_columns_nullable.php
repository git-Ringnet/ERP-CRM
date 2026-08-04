<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->string('vendor')->nullable()->change();
            $table->string('type')->nullable()->change();
            $table->string('part_number')->nullable()->change();
            $table->string('si_name')->nullable()->change();
            $table->string('eu_name_mst')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sale_order_request_items', function (Blueprint $table) {
            $table->string('vendor')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->string('part_number')->nullable(false)->change();
            $table->string('si_name')->nullable(false)->change();
            $table->string('eu_name_mst')->nullable(false)->change();
        });
    }
};

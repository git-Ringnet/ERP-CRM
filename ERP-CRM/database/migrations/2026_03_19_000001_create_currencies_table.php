<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng danh sách tiền tệ (ISO 4217)
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('ISO 4217 currency code');
            $table->string('name', 100)->comment('English name');
            $table->string('name_vi', 100)->comment('Vietnamese name');
            $table->string('symbol', 10)->comment('Currency symbol, e.g. $, €, ¥');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->boolean('is_base')->default(false)->comment('Only VND should be true');
            $table->boolean('is_active')->default(true);
            $table->enum('symbol_position', ['before', 'after'])->default('before');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};

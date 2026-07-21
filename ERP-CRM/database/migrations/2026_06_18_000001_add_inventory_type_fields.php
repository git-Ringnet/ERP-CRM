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
        Schema::create('inventory_custom_columns', function (Blueprint $table) {
            $table->id();
            $table->string('tab')->comment('Tab áp dụng: stocking, project, rmodel');
            $table->string('name')->comment('Tên hiển thị của cột');
            $table->string('key')->comment('Khóa lưu trữ JSON');
            $table->timestamps();
            
            $table->index(['tab', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_custom_columns');
    }
};

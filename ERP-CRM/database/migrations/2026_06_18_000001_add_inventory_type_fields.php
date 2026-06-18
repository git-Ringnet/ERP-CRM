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
        Schema::table('product_items', function (Blueprint $table) {
            $table->string('borrower')->nullable()->after('comments')->comment('Người mượn thiết bị');
            $table->json('custom_fields')->nullable()->after('borrower')->comment('Giá trị các cột động');
        });

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

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropColumn(['borrower', 'custom_fields']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Bảng lịch sử tỷ giá hối đoái
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('rate', 18, 6)->comment('Tỷ giá chuyển khoản (Transfer rate) - tỷ giá chuẩn');
            $table->date('effective_date')->comment('Ngày áp dụng');
            $table->enum('source', ['auto', 'manual'])->default('auto')->comment('Nguồn: auto = API Vietcombank, manual = nhập tay');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Mỗi loại tiền tệ chỉ có 1 tỷ giá/ngày
            $table->unique(['currency_id', 'effective_date'], 'unique_currency_date');
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};

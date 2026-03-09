<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 50)->unique()->comment('Mã tài sản (VD: TS-001)');
            $table->string('name', 255)->comment('Tên tài sản / dụng cụ');
            $table->string('category', 100)->comment('Danh mục (Thiết bị IT, Văn phòng, Dụng cụ...)');
            $table->enum('tracking_type', ['serial', 'quantity'])->default('serial')
                ->comment('serial = theo mã/serial (laptop...); quantity = theo số lượng (bút, ghế...)');

            // --- Trường cho serial-type ---
            $table->string('serial_number', 100)->nullable()->comment('Số serial (chỉ dùng với tracking_type=serial)');

            // --- Trường cho quantity-type ---
            $table->unsignedInteger('quantity_total')->default(1)->comment('Tổng số lượng đang quản lý');
            $table->unsignedInteger('quantity_available')->default(1)->comment('Số lượng hiện còn (chưa cấp phát)');

            // --- Thông tin chung ---
            $table->string('brand', 100)->nullable()->comment('Hãng / nhà sản xuất');
            $table->date('purchase_date')->nullable()->comment('Ngày mua');
            $table->decimal('purchase_price', 15, 2)->nullable()->comment('Giá mua (VND)');
            $table->date('warranty_expiry')->nullable()->comment('Ngày hết bảo hành');
            $table->enum('status', ['available', 'assigned', 'maintenance', 'disposed'])
                ->default('available')
                ->comment('available=sẵn sàng; assigned=đang cấp phát; maintenance=bảo trì; disposed=thanh lý');
            $table->string('location', 255)->nullable()->comment('Vị trí lưu trữ / phòng ban');
            $table->text('description')->nullable()->comment('Mô tả thêm');
            $table->string('image', 255)->nullable()->comment('Đường dẫn ảnh tài sản');
            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index('tracking_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_assets');
    }
};

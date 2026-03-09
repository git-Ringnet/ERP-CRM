<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_asset_id')->constrained('employee_assets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->comment('Nhân viên được nhận tài sản');
            $table->foreignId('assigned_by')->constrained('users')->comment('Người thực hiện cấp phát');

            $table->unsignedInteger('quantity')->default(1)->comment('Số lượng cấp phát (serial-type luôn = 1)');
            $table->date('assigned_date')->comment('Ngày cấp phát');
            $table->date('expected_return_date')->nullable()->comment('Ngày dự kiến hoàn trả');
            $table->date('returned_date')->nullable()->comment('Ngày thực tế hoàn trả');

            $table->enum('condition_at_assignment', ['new', 'good', 'fair', 'poor'])
                ->default('good')
                ->comment('Tình trạng tài sản khi giao');
            $table->enum('condition_at_return', ['new', 'good', 'fair', 'poor'])
                ->nullable()
                ->comment('Tình trạng tài sản khi thu hồi');

            $table->text('reason')->nullable()->comment('Lý do / mục đích cấp phát');
            $table->text('return_note')->nullable()->comment('Ghi chú khi thu hồi (hư hỏng, bảo trì...)');

            $table->enum('status', ['active', 'returned', 'overdue'])
                ->default('active')
                ->comment('active=đang cấp; returned=đã thu hồi; overdue=quá hạn');

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['employee_asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_asset_assignments');
    }
};

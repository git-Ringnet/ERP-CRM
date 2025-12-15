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
        // Bảng danh mục dự án (tương tự Công trình trong MISA)
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique(); // Mã dự án: DA001, DA002...
            $table->string('name'); // Tên dự án
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // Khách hàng/Chủ đầu tư
            $table->string('customer_name')->nullable(); // Cache tên khách hàng
            $table->text('address')->nullable(); // Địa chỉ dự án
            $table->text('description')->nullable(); // Mô tả/Diễn giải
            $table->decimal('budget', 18, 2)->default(0); // Dự toán/Ngân sách
            $table->date('start_date')->nullable(); // Ngày bắt đầu
            $table->date('end_date')->nullable(); // Ngày kết thúc dự kiến
            $table->enum('status', ['planning', 'in_progress', 'completed', 'cancelled', 'on_hold'])
                  ->default('planning'); // Tình trạng
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete(); // Người quản lý
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Thêm project_id vào sale_items (gắn dự án ở cấp dòng sản phẩm như MISA)
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('product_name')
                  ->constrained()->nullOnDelete();
        });

        // Thêm project_id vào sales (để filter/báo cáo nhanh theo dự án)
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('type')
                  ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::dropIfExists('projects');
    }
};

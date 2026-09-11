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
        Schema::create('marketing_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., MKT-GIFT-001
            $table->string('name');
            $table->string('category')->default('gift'); // gift: Quà tặng, publication: Ấn phẩm/Brochure, equipment: Thiết bị sự kiện, other: Khác
            $table->string('unit')->default('Cái'); // Cái, Bộ, Cuốn, Hộp...
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_alert')->default(10);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->timestamps();
        });

        Schema::create('marketing_item_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_item_id')->constrained('marketing_items')->onDelete('cascade');
            $table->string('type'); // import: Nhập kho, export: Xuất kho/Quà tặng, adjustment: Kiểm kê điều chỉnh
            $table->integer('quantity'); // Số lượng thay đổi (dương)
            $table->integer('remaining_stock'); // Tồn kho sau giao dịch
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities')->nullOnDelete();
            $table->foreignId('marketing_event_id')->nullable()->constrained('marketing_events')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_code')->nullable(); // Mã phiếu / tham chiếu
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_item_transactions');
        Schema::dropIfExists('marketing_items');
    }
};

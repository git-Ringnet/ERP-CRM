<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_revenues', function (Blueprint $table) {
            $table->id();

            // Liên kết với dữ liệu hiện có (nullable - vì có thể nhập tay hoàn toàn)
            $table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete(); // Vendor/Hãng

            // 23 cột theo template
            $table->string('cpq_number')->nullable();                    // 2. CPQ
            $table->string('invoice_status')->nullable();                // 3. Tình trạng XHĐ
            $table->string('warehouse_status')->nullable();              // 4. Hàng đã nhập kho (WH)
            $table->string('license_exported')->nullable();              // 5. Đã xuất POS (License)
            $table->string('po_code')->nullable();                       // 6. Số PO
            $table->date('po_date')->nullable();                         // 7. Ngày PO
            $table->string('product_name')->nullable();                  // 8. Hàng hóa
            $table->integer('quantity')->default(0);                     // 9. SL
            $table->string('serial_number')->nullable();                 // 10. S.N
            $table->string('quote_id')->nullable();                      // 11. Quote ID
            $table->decimal('list_price', 18, 2)->default(0);           // 12. ListPrice ($)
            $table->decimal('discount_percent', 8, 2)->default(0);      // 13. Discount %
            $table->decimal('unit_price', 18, 2)->default(0);           // 14. Unit Price ($)
            $table->decimal('total_amount', 18, 2)->default(0);         // 15. Thành Tiền (auto-calc)
            $table->date('expired_date')->nullable();                    // 16. Expired date
            $table->string('customer_name')->nullable();                 // 17. Khách hàng
            $table->decimal('selling_price', 18, 2)->default(0);        // 18. Giá bán (từ SO)
            $table->string('end_user_partner')->nullable();              // 19. End User / Partner (Project)
            $table->string('equipment')->nullable();                     // 20. Equipment
            $table->string('partner_name')->nullable();                  // 21. Partner
            $table->string('end_user')->nullable();                      // 22. EU
            $table->string('industry')->nullable();                      // 23. Industries

            // Metadata
            $table->integer('year')->default(2026);                      // Năm theo dõi
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indexes
            $table->index('year');
            $table->index('po_code');
            $table->index('customer_name');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_revenues');
    }
};

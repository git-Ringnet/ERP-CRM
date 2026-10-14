<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PO bị hủy để Sales chỉnh lại P&L / yêu cầu đặt hàng sẽ được giữ lại để
     * dùng tiếp: khi yêu cầu đặt hàng được duyệt lại, sản phẩm quay về đúng PO
     * cũ thay vì đi qua Gom đơn và tạo PO mới.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('awaiting_revision')->default(false)->after('status');
            $table->string('revision_previous_status', 50)->nullable()->after('awaiting_revision');
            $table->json('revision_sale_order_request_ids')->nullable()->after('revision_previous_status');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['awaiting_revision', 'revision_previous_status', 'revision_sale_order_request_ids']);
        });
    }
};

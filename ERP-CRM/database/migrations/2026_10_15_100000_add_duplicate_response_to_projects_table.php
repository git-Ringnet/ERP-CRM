<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales phản hồi "không trùng" (kèm lý do) cho dự án bị báo trùng để PO/PM xem và mở lại tiếp nhận.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('duplicate_response_note')->nullable()->after('duplicate_vendor_sales_info');
            $table->timestamp('duplicate_response_at')->nullable()->after('duplicate_response_note');
            $table->foreignId('duplicate_response_by')->nullable()->after('duplicate_response_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duplicate_response_by');
            $table->dropColumn(['duplicate_response_note', 'duplicate_response_at']);
        });
    }
};

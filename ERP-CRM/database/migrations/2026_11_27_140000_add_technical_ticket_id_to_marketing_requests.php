<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liên kết yêu cầu phối hợp Marketing → Ticket kỹ thuật được tạo tự động.
 * Kỹ thuật xử lý trên ticket, trạng thái đồng bộ ngược về yêu cầu Marketing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('marketing_requests', 'technical_ticket_id')) {
            return;
        }

        Schema::table('marketing_requests', function (Blueprint $table) {
            $table->foreignId('technical_ticket_id')
                ->nullable()
                ->after('assigned_to')
                ->constrained('technical_tickets', 'id', 'mkt_req_tech_ticket_foreign')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('marketing_requests', 'technical_ticket_id')) {
            return;
        }

        Schema::table('marketing_requests', function (Blueprint $table) {
            $table->dropForeign('mkt_req_tech_ticket_foreign');
            $table->dropColumn('technical_ticket_id');
        });
    }
};

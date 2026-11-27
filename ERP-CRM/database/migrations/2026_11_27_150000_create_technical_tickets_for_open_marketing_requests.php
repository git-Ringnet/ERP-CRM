<?php

use App\Models\MarketingRequest;
use App\Services\MarketingTechnicalTicketService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Tạo Ticket kỹ thuật cho các yêu cầu Marketing gửi team Kỹ thuật đang còn mở
 * (thuộc sự kiện đã duyệt / đã hoàn thành), để Kỹ thuật xử lý trên module Ticket kỹ thuật.
 */
return new class extends Migration
{
    public function up(): void
    {
        $service = app(MarketingTechnicalTicketService::class);

        MarketingRequest::with(['event', 'ticket'])
            ->where('support_team', 'technical')
            ->whereNull('technical_ticket_id')
            ->whereNotNull('marketing_event_id')
            ->whereNotIn('status', ['completed', 'rejected'])
            ->whereHas('event', fn ($e) => $e->whereIn('status', ['approved', 'completed']))
            ->orderBy('id')
            ->get()
            ->each(function (MarketingRequest $req) use ($service) {
                try {
                    $ticket = $service->createForRequest($req);
                    Log::info("Marketing request {$req->code} -> technical ticket {$ticket?->code}");
                } catch (\Throwable $e) {
                    Log::error("Không tạo được ticket kỹ thuật cho {$req->code}: " . $e->getMessage());
                }
            });
    }

    public function down(): void
    {
        // Giữ nguyên ticket kỹ thuật đã tạo (có thể đã được xử lý)
    }
};

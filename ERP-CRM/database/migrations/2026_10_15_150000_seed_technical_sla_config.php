<?php

use App\Models\Setting;
use App\Models\TechnicalTicket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * TECH 1: cấu hình SLA ban đầu theo yêu cầu - giờ làm việc 08:00-12:00 và 13:30-17:30,
     * thứ Hai đến thứ Sáu. Có thể thay đổi tại Cài đặt hệ thống -> SLA Ticket kỹ thuật.
     */
    public function up(): void
    {
        if (Setting::where('key', TechnicalTicket::SLA_SETTING_KEY)->exists()) {
            return; // đã cấu hình thì giữ nguyên
        }

        Setting::create([
            'key' => TechnicalTicket::SLA_SETTING_KEY,
            'value' => json_encode(TechnicalTicket::SLA_DEFAULTS),
            'group' => 'technical',
        ]);
        Cache::forget('setting.' . TechnicalTicket::SLA_SETTING_KEY);
    }

    public function down(): void
    {
        Setting::where('key', TechnicalTicket::SLA_SETTING_KEY)->delete();
        Cache::forget('setting.' . TechnicalTicket::SLA_SETTING_KEY);
    }
};

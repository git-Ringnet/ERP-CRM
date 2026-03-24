<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exchange-rates:fetch
                            {--date= : Ngày cần fetch tỷ giá (YYYY-MM-DD). Mặc định: hôm nay}
                            {--backfill= : Backfill N ngày gần nhất}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch tỷ giá hối đoái từ Vietcombank và lưu vào database';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateService $service): int
    {
        // Backfill mode
        if ($days = $this->option('backfill')) {
            return $this->handleBackfill($service, (int) $days);
        }

        // Single date mode
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $this->info("🔄 Đang fetch tỷ giá cho ngày {$date->toDateString()}...");

        $result = $service->fetchAndStore($date);

        if ($result['success']) {
            $this->info("✅ Thành công! Cập nhật {$result['currencies_updated']} tỷ giá.");
        } else {
            $this->error("❌ Thất bại!");
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->warn("  ⚠ {$error}");
            }
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Backfill tỷ giá cho N ngày gần nhất
     */
    protected function handleBackfill(ExchangeRateService $service, int $days): int
    {
        $this->info("🔄 Backfill tỷ giá cho {$days} ngày gần nhất...");
        $bar = $this->output->createProgressBar($days);

        $successCount = 0;
        $failCount = 0;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $result = $service->fetchAndStore($date);

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ Hoàn tất: {$successCount} ngày thành công, {$failCount} ngày thất bại.");

        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}

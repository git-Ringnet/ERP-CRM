<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateDifferenceService;
use Illuminate\Console\Command;

class RevalueCurrencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:revalue {date? : The revaluation date (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revalue outstanding foreign currency debts (unrealized gain/loss)';

    /**
     * Execute the console command.
     */
    public function handle(ExchangeRateDifferenceService $service)
    {
        $date = $this->argument('date') ?: now()->format('Y-m-d');
        
        $this->info("Starting revaluation for date: {$date}...");
        
        try {
            $service->revalueOutstandingDebts($date);
            $this->info("Revaluation completed successfully.");
        } catch (\Exception $e) {
            $this->error("Error during revaluation: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}

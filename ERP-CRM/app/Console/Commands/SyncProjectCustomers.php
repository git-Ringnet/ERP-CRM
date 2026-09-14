<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class SyncProjectCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:sync-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $signatureDescription = 'Tự động quét và đồng bộ khách hàng cho toàn bộ dự án';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Bắt đầu đồng bộ danh mục Khách hàng từ Dự án...');

        $projects = Project::all();
        $total = $projects->count();
        $synced = 0;

        foreach ($projects as $project) {
            $customer = $project->findOrCreateCustomerFromProject();
            if ($customer) {
                $synced++;
                $this->line("  [OK] Dự án {$project->code} -> Khách hàng: {$customer->name} (#{$customer->id})");
            } else {
                $this->warn("  [SKIP] Dự án {$project->code} không có thông tin đối tác/khách hàng");
            }
        }

        $this->info("Đã hoàn tất đồng bộ! ({$synced}/{$total} dự án)");

        return Command::SUCCESS;
    }
}

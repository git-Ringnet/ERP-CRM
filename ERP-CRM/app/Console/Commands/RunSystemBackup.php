<?php

namespace App\Console\Commands;

use App\Services\SystemBackupService;
use Illuminate\Console\Command;

class RunSystemBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:system 
                            {--scope=full : Phạm vi sao lưu: full (Database + Đính kèm) hoặc db_only (Chỉ Database)}
                            {--destination= : Đường dẫn thư mục lưu trữ sao lưu (tùy chọn)}
                            {--password= : Mật khẩu mã hóa bảo vệ bản sao lưu (tùy chọn)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chạy tiến trình tự động Sao lưu toàn diện Hệ thống (Database + Toàn bộ tệp đính kèm trên mọi ổ đĩa)';

    /**
     * Execute the console command.
     */
    public function handle(SystemBackupService $backupService): int
    {
        $scope = $this->option('scope') ?: 'full';
        $destination = $this->option('destination');
        $password = $this->option('password');

        $this->info("=======================================================");
        $this->info("🚀 BẮT ĐẦU TIẾN TRÌNH SAO LƯU TỰ ĐỘNG HỆ THỐNG (CRONJOB)");
        $this->info("=======================================================");
        $this->line("Thời gian: " . now()->toDateTimeString());
        $this->line("Phạm vi: " . ($scope === 'full' ? 'Toàn diện (Database + Tệp đính kèm)' : 'Chỉ Database'));

        // Display scanned attachment sources
        if ($scope === 'full') {
            $attachmentFolders = $backupService->getAttachmentDirectories();
            $this->line("\n📁 Các nguồn thư mục tệp đính kèm được nhận diện:");
            foreach ($attachmentFolders as $prefix => $path) {
                $this->line("   - [{$prefix}]: {$path}");
            }
        }

        $this->line("\n⏳ Đang kết xuất CSDL và đóng gói tệp tin...");

        try {
            $result = $backupService->runBackup($scope, $destination, $password);

            $this->info("\n SAO LƯU THÀNH CÔNG!");
            $this->table(
                ['Thuộc tính', 'Thông tin'],
                [
                    ['Tên tệp sao lưu', $result['filename']],
                    ['Đường dẫn lưu trữ', $result['path']],
                    ['Dung lượng gói sao lưu', $result['size']],
                    ['Dung lượng CSDL gốc', $result['db_size']],
                    ['Mã hóa bảo vệ', $result['encrypted'] ? 'Có (AES-256-CBC)' : 'Không (Standard Archive)'],
                    ['Số file cũ đã dọn dẹp', $result['purged_old_backups'] . ' file'],
                ]
            );

            if (!empty($result['attachments_included'])) {
                $rows = [];
                foreach ($result['attachments_included'] as $prefix => $info) {
                    $rows[] = [$prefix, $info['source_path'], $info['files_count'] . ' tệp tin'];
                }
                $this->info("\n📊 Chi tiết tệp đính kèm đã đóng gói:");
                $this->table(['Nhãn lưu trữ', 'Đường dẫn nguồn', 'Số tệp tin'], $rows);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("\n❌ LỖI TRONG QUÁ TRÌNH SAO LƯU: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

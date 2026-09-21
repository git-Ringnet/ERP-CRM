<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanDatabaseAndStorageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-db-storage {--force : Bỏ qua bước xác nhận trực tiếp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa sạch dữ liệu nghiệp vụ database và toàn bộ file đính kèm trong storage, giữ lại nhân viên, khách hàng, nhà cung cấp, sản phẩm và phân quyền';

    /**
     * Whitelist of tables that must NOT be cleared.
     *
     * @var array
     */
    protected array $preservedTables = [
        // 1. Nhân viên, Tài khoản & Phân quyền
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'user_roles',
        'user_permissions',
        'work_locations',
        'salary_components',
        'employee_salary_components',
        'skills',
        'skill_categories',
        'employee_skills',
        'work_schedules',

        // 2. Khách hàng & Nhà cung cấp
        'customers',
        'contacts',
        'suppliers',
        'supplier_contacts',
        'customer_care_stages',
        'care_milestones',
        'milestone_templates',
        'template_milestones',

        // 3. Sản phẩm, Kho & Cấu hình hệ thống
        'products',
        'warehouses',
        'migrations',
        'currencies',
        'exchange_rates',
        'settings',
        'po_company_config',
        'supplier_po_configs',
        'payment_templates',
        'payment_template_items',
        'approval_workflows',
        'approval_levels',
        'transaction_categories',
        'cash_flow_config_items',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->warn('⚠  CÔNG CỤ DỌN DẸP DATABASE & FILE ĐÍNH KÈM STORAGE');
        $this->info('================================================================');
        $this->line('  • Giữ lại: users (nhân viên), customers, suppliers, products, roles, permissions, settings...');
        $this->line('  • Xóa: Toàn bộ dữ liệu nghiệp vụ giao dịch, bán hàng, mua hàng, kho, crm...');
        $this->line('  • Xóa: Toàn bộ file đính kèm trong storage/app/public & storage/app/*');
        $this->info('================================================================');
        $this->newLine();

        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ Đang chạy trên môi trường PRODUCTION! Vui lòng dùng --force nếu bạn chắc chắn.');
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            $confirm = $this->readConsoleInput('Bạn có chắc chắn muốn xóa toàn bộ dữ liệu và file đính kèm (giữ lại nhân viên)? (yes/no) [no]: ');
            if (!in_array(strtolower(trim($confirm)), ['yes', 'y'])) {
                $this->warn('❌ Đã hủy thao tác.');
                return self::SUCCESS;
            }
        }

        $this->info('🔄 Bắt đầu dọn dẹp Database...');

        // 1. Dọn dẹp database
        try {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            $dbName = config('database.connections.mysql.database');
            $tables = DB::select('SHOW TABLES');
            $key = "Tables_in_" . $dbName;

            $clearedTables = 0;
            $clearedRows = 0;

            foreach ($tables as $tableObj) {
                $tableName = $tableObj->$key ?? array_values((array)$tableObj)[0];

                if (in_array($tableName, $this->preservedTables)) {
                    $keepCount = DB::table($tableName)->count();
                    $this->line("  [GIỮ LẠI] Bảng `{$tableName}` ({$keepCount} bản ghi)");
                    continue;
                }

                $rowCount = DB::table($tableName)->count();
                DB::table($tableName)->truncate();
                $this->info("  [ĐÃ XÓA]  Bảng `{$tableName}` (đã xóa {$rowCount} dòng)");
                $clearedTables++;
                $clearedRows += $rowCount;
            }

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            $this->newLine();
            $this->info("✅ Hoàn tất dọn dẹp Database: Đã làm sạch {$clearedTables} bảng với tổng cộng {$clearedRows} bản ghi.");
        } catch (\Exception $e) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
            $this->error('❌ Lỗi khi dọn dẹp database: ' . $e->getMessage());
            Log::error('CleanDatabaseAndStorageCommand DB error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }

        // 2. Dọn dẹp files trong storage
        $this->newLine();
        $this->info('🔄 Bắt đầu dọn dẹp File trong Storage...');

        $storageApp = storage_path('app');
        $deletedFilesCount = 0;

        $targetDirs = [
            $storageApp . '/public',
            $storageApp . '/technical_tickets',
            $storageApp . '/temp',
            $storageApp . '/exports',
        ];

        foreach ($targetDirs as $targetDir) {
            if (!File::isDirectory($targetDir)) {
                continue;
            }

            $deletedFilesCount += $this->cleanDirectoryFiles($targetDir);
        }

        $this->info("✅ Hoàn tất dọn dẹp Storage: Đã xóa sạch {$deletedFilesCount} file đính kèm.");

        $this->newLine();
        $this->info('================================================================');
        $this->info('🎉 HOÀN THÀNH TOÀN BỘ QUÁ TRÌNH DỌN DẸP HỆ THỐNG THÀNH CÔNG!');
        $this->info('   Tất cả tài khoản nhân viên vẫn sẵn sàng để đăng nhập bình thường.');
        $this->info('================================================================');

        return self::SUCCESS;
    }

    /**
     * Delete files inside a directory recursively, preserving .gitignore and directory structure.
     */
    private function cleanDirectoryFiles(string $dir): int
    {
        $deleted = 0;
        if (!is_dir($dir)) {
            return 0;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitignore') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($fullPath)) {
                $deleted += $this->cleanDirectoryFiles($fullPath);
                // Optionally delete sub-sub folders if empty (except root storage dirs)
                $subItems = array_diff(scandir($fullPath), ['.', '..', '.gitignore']);
                if (empty($subItems) && !in_array(basename($fullPath), [
                    'bom', 'invoices', 'marketing_attachments', 'marketing_requests',
                    'milestone-exceptions', 'opportunity-attachments', 'payment-exceptions',
                    'payment-proofs', 'pnl-attachments', 'po-licenses', 'project_notes',
                    'sale-attachments', 'sale-order-requests', 'uploads', 'vendor_quotes',
                    'technical_tickets', 'temp', 'exports', 'public'
                ])) {
                    @rmdir($fullPath);
                }
            } else {
                if (@unlink($fullPath)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Read interactive console input.
     */
    private function readConsoleInput(string $prompt): string
    {
        if (function_exists('readline')) {
            $input = readline($prompt);
            if ($input !== false) {
                return $input;
            }
        }

        $this->output->write($prompt);
        @stream_set_blocking(STDIN, true);
        return fgets(STDIN) ?: '';
    }
}

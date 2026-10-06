<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\PaymentHistory;
use App\Models\SalesRevenue;
use App\Models\FinancialTransaction;
use App\Models\Export;
use App\Models\ApprovalHistory;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:delete 
                            {--id=* : IDs of the sales orders to delete} 
                            {--code=* : Codes of the sales orders to delete} 
                            {--all : Delete all sales orders} 
                            {--force : Skip safety confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa các đơn hàng bán và toàn bộ dữ liệu liên quan (Hóa đơn, Yêu cầu đặt hàng, Chi phí P&L, File đính kèm, Giao dịch liên quan)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->warn('           CÔNG CỤ XÓA ĐƠN HÀNG BÁN & DỮ LIỆU LIÊN QUAN');
        $this->info('================================================================');

        $ids = $this->option('id');
        $codes = $this->option('code');
        $all = $this->option('all');
        $force = $this->option('force');

        // Interactive mode if no options are specified
        if (empty($ids) && empty($codes) && !$all) {
            $input = $this->readConsoleInput('Nhập mã đơn (code) hoặc ID đơn hàng bán cần xóa (hoặc nhập "all" để xóa tất cả): ');
            $input = trim($input);

            if (empty($input)) {
                $this->error('❌ Vui lòng cung cấp mã đơn hàng, ID hoặc chọn option --all.');
                return self::FAILURE;
            }

            if (strtolower($input) === 'all') {
                $all = true;
            } elseif (is_numeric($input)) {
                $ids = [$input];
            } else {
                $codes = [$input];
            }
        }

        // Fetch target sales
        $query = Sale::query();

        if (!$all) {
            $query->where(function($q) use ($ids, $codes) {
                if (!empty($ids)) {
                    $q->orWhereIn('id', $ids);
                }
                if (!empty($codes)) {
                    $q->orWhereIn('code', $codes);
                }
            });
        }

        $sales = $query->get();

        if ($sales->isEmpty()) {
            $this->error('❌ Không tìm thấy đơn hàng bán nào khớp với tiêu chí đã chọn.');
            return self::FAILURE;
        }

        // Warning and Confirmation
        $this->warn('⚠ CẢNH BÁO: Dữ liệu liên quan sau đây sẽ bị xóa hoàn toàn khỏi hệ thống:');
        $this->line('  1. Thông tin đơn hàng bán và các sản phẩm trong đơn (Sales, Sale Items)');
        $this->line('  2. Chi phí phân bổ P&L liên quan (Sale Expenses)');
        $this->line('  3. Lịch sử duyệt P&L và tài liệu duyệt đính kèm (Approval Histories, PNL Attachments)');
        $this->line('  4. Các yêu cầu đặt hàng (PR - Sale Order Requests, Items, Attachments)');
        $this->line('  5. Các yêu cầu xuất hóa đơn nháp/chính thức và file hóa đơn (Invoice Requests)');
        $this->line('  6. Lịch sử thanh toán công nợ khách hàng (Payment Histories)');
        $this->line('  7. Báo cáo doanh thu bán hàng liên quan (Sales Revenues)');
        $this->line('  8. Các phiếu xuất kho liên kết (Exports & Export Items)');
        $this->line('  9. Các giao dịch tài chính thu/chi liên quan (Financial Transactions)');
        $this->info('================================================================');
        $this->newLine();

        $this->info('Danh sách đơn hàng bán chuẩn bị xóa:');
        $headers = ['ID', 'Mã Đơn', 'Khách Hàng', 'Ngày Tạo', 'Trạng Thái', 'Tổng Tiền (VND)'];
        $rows = $sales->map(fn($sale) => [
            $sale->id,
            $sale->code,
            $sale->customer_name,
            $sale->date ? $sale->date->format('d/m/Y') : '-',
            $sale->status_label,
            number_format($sale->total) . ' đ'
        ])->toArray();
        $this->table($headers, $rows);

        if (!$force) {
            $confirm = $this->readConsoleInput('Bạn có chắc chắn muốn xóa ' . $sales->count() . ' đơn hàng trên cùng toàn bộ dữ liệu liên quan không? (yes/no) [no]: ');
            if (!in_array(strtolower(trim($confirm)), ['yes', 'y'])) {
                $this->warn('❌ Đã hủy thao tác xóa dữ liệu.');
                return self::SUCCESS;
            }

            // Double check for safety
            $confirmDouble = $this->readConsoleInput('Hành động này KHÔNG THỂ HOÀN TÁC! Nhập "yes" một lần nữa để xác nhận chắc chắn: ');
            if (strtolower(trim($confirmDouble)) !== 'yes') {
                $this->warn('❌ Đã hủy thao tác xóa dữ liệu.');
                return self::SUCCESS;
            }
        }

        $this->info('🔄 Đang bắt đầu quá trình xóa các đơn hàng bán...');

        $deletionService = app(\App\Services\SaleDeletionService::class);
        $deletedCount = 0;

        foreach ($sales as $sale) {
            $this->comment("-> Đang xóa đơn hàng {$sale->code} (ID: {$sale->id})...");
            try {
                $deletionService->deleteSaleCascade($sale, true);
                $deletedCount++;
                $this->info("   ✅ Đã xóa thành công đơn hàng {$sale->code}");
            } catch (\Exception $e) {
                $this->error("   ❌ Lỗi khi xóa đơn {$sale->code}: " . $e->getMessage());
                Log::error("DeleteSales command failed for {$sale->code}: " . $e->getMessage());
            }
        }
        $this->newLine();
        $this->info("🎉 THÀNH CÔNG: Đã xóa hoàn toàn {$deletedCount} đơn hàng bán và dữ liệu liên quan!");
        return self::SUCCESS;
    }

    /**
     * Xóa file khỏi storage
     */
    protected function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Đọc input từ console có hỗ trợ block và kiểm tra readline
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

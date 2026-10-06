<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ClearUnusedProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:clear-unused
                            {--force : Bỏ qua bước xác nhận (dùng khi chạy tự động hoặc script)}
                            {--dry-run : Chỉ quét kiểm tra số lượng và danh sách, không thực hiện xóa}
                            {--chunk=1000 : Số lượng sản phẩm xóa trong mỗi lần (batch size)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa các sản phẩm không sử dụng, bảo toàn tất cả sản phẩm đang dùng trong Báo giá, Đơn bán, Đặt hàng hãng (PO), Kho hàng và các chứng từ liên quan.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->info('       CÔNG CỤ DỌN DẸP SẢN PHẨM RÁC / KHÔNG SỬ DỤNG');
        $this->info('================================================================');
        $this->newLine();

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');
        $chunkSize = max(100, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('🔍 Đang chạy ở chế độ [DRY-RUN]: Chỉ quét thống kê, KHÔNG xóa dữ liệu.');
            $this->newLine();
        }

        $this->info('Đang quét các bảng liên quan để tìm sản phẩm đang được sử dụng...');

        // Danh sách tất cả các bảng và nghiệp vụ có thể tham chiếu đến products
        $modules = [
            'quotation_items'           => 'Báo giá (Quotations)',
            'sale_items'                => 'Đơn hàng bán (Sales Orders)',
            'purchase_order_items'      => 'Đặt hàng với hãng / Đơn mua (PO)',
            'sale_order_request_items'  => 'Yêu cầu đặt hàng (Sale Order Requests)',
            'purchase_request_items'    => 'Yêu cầu mua hàng (Purchase Requests)',
            'supplier_quotation_items'  => 'Báo giá nhà cung cấp',
            'product_items'             => 'Sản phẩm/Serial trong kho (Product Items)',
            'inventories'               => 'Tồn kho (Inventories)',
            'import_items'              => 'Phiếu nhập kho (Import Items)',
            'export_items'              => 'Phiếu xuất kho (Export Items)',
            'transfer_items'            => 'Phiếu chuyển kho (Transfer Items)',
            'damaged_goods'             => 'Hàng hư hỏng (Damaged Goods)',
            'ticket_items'              => 'Ticket kỹ thuật / Mượn hàng',
            'shipping_allocation_items' => 'Phân bổ vận chuyển',
            'sales_revenues'            => 'Doanh thu bán hàng',
            'price_list_items'          => 'Bảng giá sản phẩm',
        ];

        $allInUseIds = [];
        $breakdownRows = [];

        foreach ($modules as $tableName => $label) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'product_id')) {
                $ids = DB::table($tableName)
                    ->whereNotNull('product_id')
                    ->distinct()
                    ->pluck('product_id')
                    ->toArray();

                $count = count($ids);
                $breakdownRows[] = [$label, $tableName, number_format($count)];
                $allInUseIds = array_merge($allInUseIds, $ids);
            }
        }

        // Lấy danh sách ID duy nhất đang được sử dụng
        $allInUseIds = array_values(array_unique(array_filter($allInUseIds)));
        $inUseCount = count($allInUseIds);

        // Hiển thị bảng chi tiết
        $this->table(['Nghiệp vụ / Chức năng', 'Tên bảng', 'Số sản phẩm đang dùng'], $breakdownRows);
        $this->newLine();

        $totalProducts = DB::table('products')->count();

        // Query tìm sản phẩm không sử dụng
        $unusedQuery = DB::table('products')->whereNotIn('id', $allInUseIds);
        $unusedCount = $unusedQuery->count();

        $this->info("----------------------------------------------------------------");
        $this->info("📊 TỔNG KẾT QUẢ QUÉT:");
        $this->line(" - Tổng số sản phẩm trong hệ thống:              <fg=cyan>" . number_format($totalProducts) . "</>");
        $this->line(" - Số sản phẩm đang được sử dụng (CẦN GIỮ LẠI):   <fg=green>" . number_format($inUseCount) . "</>");
        $this->line(" - Số sản phẩm không sử dụng (SẼ BỊ CLEAR):       <fg=yellow>" . number_format($unusedCount) . "</>");
        $this->info("----------------------------------------------------------------");
        $this->newLine();

        if ($unusedCount === 0) {
            $this->info('✅ Không có sản phẩm rác nào cần dọn dẹp. Hệ thống đã tối ưu!');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("🔍 Chế độ DRY-RUN kết thúc: Có " . number_format($unusedCount) . " sản phẩm có thể xóa an toàn.");
            $this->line("Để thực hiện xóa thật, hãy chạy lệnh không có tham số --dry-run:");
            $this->line("  <fg=cyan>php artisan products:clear-unused</>");
            return Command::SUCCESS;
        }

        if (!$force) {
            $confirmed = $this->confirm(
                "⚠️  Bạn có chắc chắn muốn XÓA VĨNH VIỄN " . number_format($unusedCount) . " sản phẩm không sử dụng này không?",
                false
            );

            if (!$confirmed) {
                $this->warn('⛔ Đã hủy thao tác. Không có dữ liệu nào bị thay đổi.');
                return Command::SUCCESS;
            }
        }

        $this->info("Bắt đầu xóa {$unusedCount} sản phẩm không sử dụng theo từng đợt ({$chunkSize} sản phẩm/lần)...");
        $startTime = microtime(true);

        $bar = $this->output->createProgressBar($unusedCount);
        $bar->start();

        $totalDeleted = 0;

        try {
            DB::beginTransaction();

            // Lấy danh sách ID cần xóa và chia nhỏ thành từng chunk để xóa an toàn
            $unusedIds = DB::table('products')
                ->whereNotIn('id', $allInUseIds)
                ->pluck('id')
                ->toArray();

            $chunks = array_chunk($unusedIds, $chunkSize);

            foreach ($chunks as $chunk) {
                $deleted = DB::table('products')->whereIn('id', $chunk)->delete();
                $totalDeleted += $deleted;
                $bar->advance(count($chunk));
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

            $elapsed = round(microtime(true) - $startTime, 2);

            $this->info("================================================================");
            $this->info("✅ ĐÃ HOÀN THÀNH DỌN DẸP SẢN PHẨM THÀNH CÔNG!");
            $this->line(" - Số sản phẩm đã xóa:      <fg=green>" . number_format($totalDeleted) . "</>");
            $this->line(" - Số sản phẩm còn lại:     <fg=cyan>" . number_format(DB::table('products')->count()) . "</>");
            $this->line(" - Thời gian thực hiện:     {$elapsed}s");
            $this->info("================================================================");

            Log::info("Command products:clear-unused: Deleted {$totalDeleted} unused products in {$elapsed}s.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error("❌ Có lỗi xảy ra trong quá trình xóa: " . $e->getMessage());
            Log::error("Command products:clear-unused failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}

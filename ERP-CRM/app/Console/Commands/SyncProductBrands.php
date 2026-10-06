<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductBrands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-brands
                            {--dry-run : Chỉ quét thống kê, không thực hiện cập nhật}
                            {--all : Cập nhật lại toàn bộ sản phẩm (kể cả những sản phẩm đã có hãng)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ lại Hãng (Brand) cho các sản phẩm từ Phiếu nhập kho, Đơn mua hàng PO và Bảng giá nhà cung cấp.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->info('      CÔNG CỤ ĐỒNG BỘ HÃNG (BRAND) CHO SẢN PHẨM');
        $this->info('================================================================');
        $this->newLine();

        $dryRun = (bool) $this->option('dry-run');
        $syncAll = (bool) $this->option('all');

        if ($dryRun) {
            $this->warn('🔍 Đang chạy ở chế độ [DRY-RUN]: Chỉ quét thống kê, KHÔNG cập nhật CSDL.');
            $this->newLine();
        }

        $this->info('Đang thu thập dữ liệu Hãng từ các nguồn: Nhập kho, Đơn mua PO, Bảng giá NCC...');

        // 1. Nguồn 1: Phiếu nhập kho (Ưu tiên cao nhất)
        $importRows = DB::select("
            SELECT ii.product_id, s.name as brand
            FROM import_items ii
            JOIN imports i ON i.id = ii.import_id
            JOIN suppliers s ON s.id = i.supplier_id
            WHERE s.name IS NOT NULL AND s.name != '' AND ii.product_id IS NOT NULL
            ORDER BY i.date DESC
        ");

        // 2. Nguồn 2: Đơn mua hàng PO
        $poRows = DB::select("
            SELECT poi.product_id, s.name as brand
            FROM purchase_order_items poi
            JOIN purchase_orders po ON po.id = poi.purchase_order_id
            JOIN suppliers s ON s.id = po.supplier_id
            WHERE s.name IS NOT NULL AND s.name != '' AND poi.product_id IS NOT NULL
            ORDER BY po.created_at DESC
        ");

        // 3. Nguồn 3: Bảng giá nhà cung cấp (Khớp theo mã SKU)
        $splRows = DB::select("
            SELECT spli.sku, s.name as brand
            FROM supplier_price_list_items spli
            JOIN supplier_price_lists spl ON spl.id = spli.supplier_price_list_id
            JOIN suppliers s ON s.id = spl.supplier_id
            WHERE s.name IS NOT NULL AND s.name != '' AND spli.sku IS NOT NULL AND spli.sku != ''
            ORDER BY spl.effective_date DESC, spl.created_at DESC
        ");

        $assignedProductIds = [];
        $brandToProductIds = [];
        $sourceBreakdown = [
            'imports'     => 0,
            'pos'         => 0,
            'price_lists' => 0,
        ];

        // Lấy danh sách sản phẩm cần cập nhật
        $productQuery = DB::table('products');
        if (!$syncAll) {
            $productQuery->where(function ($q) {
                $q->whereNull('brand')->orWhere('brand', '');
            });
        }
        $targetProducts = $productQuery->pluck('code', 'id')->toArray();

        // Map từ Nhập kho
        foreach ($importRows as $r) {
            if (isset($targetProducts[$r->product_id]) && !isset($assignedProductIds[$r->product_id])) {
                $assignedProductIds[$r->product_id] = true;
                $brandToProductIds[$r->brand][] = $r->product_id;
                $sourceBreakdown['imports']++;
            }
        }

        // Map từ Đơn mua hàng PO
        foreach ($poRows as $r) {
            if (isset($targetProducts[$r->product_id]) && !isset($assignedProductIds[$r->product_id])) {
                $assignedProductIds[$r->product_id] = true;
                $brandToProductIds[$r->brand][] = $r->product_id;
                $sourceBreakdown['pos']++;
            }
        }

        // Map từ Bảng giá NCC (theo SKU = code)
        $skuToBrand = [];
        foreach ($splRows as $r) {
            if (!isset($skuToBrand[$r->sku])) {
                $skuToBrand[$r->sku] = $r->brand;
            }
        }

        foreach ($targetProducts as $id => $code) {
            if (!isset($assignedProductIds[$id]) && isset($skuToBrand[$code])) {
                $brand = $skuToBrand[$code];
                $assignedProductIds[$id] = true;
                $brandToProductIds[$brand][] = $id;
                $sourceBreakdown['price_lists']++;
            }
        }

        $totalAssigned = count($assignedProductIds);

        // Hiển thị bảng thống kê nguồn
        $this->table(
            ['Nguồn dữ liệu', 'Số sản phẩm khớp'],
            [
                ['Phiếu Nhập kho (Imports)', number_format($sourceBreakdown['imports'])],
                ['Đơn mua hàng hãng (Purchase Orders)', number_format($sourceBreakdown['pos'])],
                ['Bảng giá nhà cung cấp (Price Lists)', number_format($sourceBreakdown['price_lists'])],
                ['TỔNG CỘNG', number_format($totalAssigned)],
            ]
        );
        $this->newLine();

        // Hiển thị chi tiết từng hãng
        $brandTableRows = [];
        foreach ($brandToProductIds as $brand => $ids) {
            $brandTableRows[] = [$brand, number_format(count($ids))];
        }
        $this->table(['Hãng / Nhà sản xuất', 'Số sản phẩm'], $brandTableRows);
        $this->newLine();

        if ($totalAssigned === 0) {
            $this->info('✅ Tất cả sản phẩm đã có hãng hoặc không tìm thấy thông tin hãng mới để cập nhật.');
            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("🔍 Chế độ DRY-RUN kết thúc: Có " . number_format($totalAssigned) . " sản phẩm có thể cập nhật Hãng.");
            $this->line("Để thực hiện cập nhật thật, chạy lệnh:");
            $this->line("  <fg=cyan>php artisan products:sync-brands</>");
            return Command::SUCCESS;
        }

        $this->info("Bắt đầu cập nhật Hãng cho {$totalAssigned} sản phẩm...");
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            $processedCount = 0;
            foreach ($brandToProductIds as $brand => $ids) {
                foreach (array_chunk($ids, 1000) as $chunk) {
                    DB::table('products')->whereIn('id', $chunk)->update(['brand' => $brand]);
                    $processedCount += count($chunk);
                }
            }

            DB::commit();

            $elapsed = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info("================================================================");
            $this->info("✅ ĐỒNG BỘ HÃNG CHO SẢN PHẨM THÀNH CÔNG!");
            $this->line(" - Số sản phẩm đã đồng bộ Hãng:  <fg=green>" . number_format($processedCount) . "</>");
            $this->line(" - Thời gian thực hiện:          {$elapsed}s");
            $this->info("================================================================");

            Log::info("Command products:sync-brands: Updated brand for {$processedCount} products in {$elapsed}s.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error("❌ Có lỗi xảy ra trong quá trình cập nhật: " . $e->getMessage());
            Log::error("Command products:sync-brands failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}

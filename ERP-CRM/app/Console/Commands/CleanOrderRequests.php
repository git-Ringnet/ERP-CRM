<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\SaleOrderRequestAttachment;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CleanOrderRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pr:clean
                            {--id=* : ID cụ thể của Yêu cầu đặt hàng (PR) cần xóa}
                            {--code=* : Mã PR (code, VD: SOR2603-0001) cụ thể cần xóa}
                            {--sale-code=* : Mã đơn hàng bán (SO code) để dọn dẹp các PR thuộc đơn đó}
                            {--orphaned : Xóa các PR không còn đơn hàng bán (Sale) tương ứng trong hệ thống}
                            {--empty : Xóa các PR rỗng không có bất kỳ sản phẩm nào}
                            {--cancelled : Xóa các PR có toàn bộ sản phẩm đã bị hủy}
                            {--trashed : Dọn sạch vĩnh viễn các PR trong thùng rác (soft-deleted)}
                            {--all-invalid : Quét và dọn sạch toàn bộ PR mồ côi, rỗng, bị hủy toàn phần hoặc lỗi dư thừa}
                            {--dry-run : Chỉ quét kiểm tra và báo cáo số lượng, không thực hiện xóa}
                            {--force : Bỏ qua các bước xác nhận trực tiếp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dọn sạch các yêu cầu đặt hàng (PR) bị lỗi, bị dư, mồ côi hoặc không hợp lệ';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->info('       CÔNG CỤ DỌN DẸP YÊU CẦU ĐẶT HÀNG (PR) BỊ LỖI / BỊ DƯ     ');
        $this->info('================================================================');

        $specificIds = $this->option('id') ?: [];
        $specificCodes = $this->option('code') ?: [];
        $saleCodes = $this->option('sale-code') ?: [];
        $cleanOrphaned = $this->option('orphaned');
        $cleanEmpty = $this->option('empty');
        $cleanCancelled = $this->option('cancelled');
        $cleanTrashed = $this->option('trashed');
        $cleanAllInvalid = $this->option('all-invalid');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Nếu không truyền cờ cụ thể nào, mặc định quét chế độ --all-invalid
        $isDefaultScan = empty($specificIds) && empty($specificCodes) && empty($saleCodes)
            && !$cleanOrphaned && !$cleanEmpty && !$cleanCancelled && !$cleanTrashed;

        if ($isDefaultScan) {
            $cleanAllInvalid = true;
        }

        $prQuery = SaleOrderRequest::withTrashed();
        $targetPrIds = collect();
        $scanReasons = [];

        // 1. Lọc theo ID cụ thể
        if (!empty($specificIds)) {
            $foundByIds = SaleOrderRequest::withTrashed()->whereIn('id', $specificIds)->pluck('id');
            foreach ($foundByIds as $id) {
                $targetPrIds->push($id);
                $scanReasons[$id] = 'Chỉ định theo ID';
            }
        }

        // 2. Lọc theo Mã PR (code)
        if (!empty($specificCodes)) {
            $foundByCodes = SaleOrderRequest::withTrashed()->whereIn('code', $specificCodes)->pluck('id', 'code');
            foreach ($foundByCodes as $code => $id) {
                $targetPrIds->push($id);
                $scanReasons[$id] = "Chỉ định theo mã: {$code}";
            }
        }

        // 3. Lọc theo mã đơn hàng bán (SO code)
        if (!empty($saleCodes)) {
            $saleIds = Sale::whereIn('code', $saleCodes)->pluck('id');
            $foundBySales = SaleOrderRequest::withTrashed()->whereIn('sale_id', $saleIds)->pluck('id', 'code');
            foreach ($foundBySales as $code => $id) {
                $targetPrIds->push($id);
                $scanReasons[$id] = "Thuộc đơn bán chỉ định: " . implode(',', $saleCodes);
            }
        }

        // 4. Quét PR mồ côi (Sale đã bị xóa)
        if ($cleanOrphaned || $cleanAllInvalid) {
            $orphans = SaleOrderRequest::withTrashed()
                ->whereNotNull('sale_id')
                ->whereDoesntHave('sale')
                ->pluck('id', 'code');
            foreach ($orphans as $code => $id) {
                $targetPrIds->push($id);
                $scanReasons[$id] = 'Mồ côi (Đơn bán SO không tồn tại)';
            }
        }

        // 5. Quét PR rỗng (không có items)
        if ($cleanEmpty || $cleanAllInvalid) {
            $emptyPrs = SaleOrderRequest::withTrashed()
                ->doesntHave('items')
                ->pluck('id', 'code');
            foreach ($emptyPrs as $code => $id) {
                $targetPrIds->push($id);
                $scanReasons[$id] = 'PR rỗng (không có sản phẩm nào)';
            }
        }

        // 6. Quét PR có toàn bộ sản phẩm đã bị hủy
        if ($cleanCancelled || $cleanAllInvalid) {
            $allCancelledPrs = SaleOrderRequest::withTrashed()
                ->has('items')
                ->whereDoesntHave('items', function ($q) {
                    $q->where('is_cancelled', false);
                })
                ->pluck('id', 'code');
            foreach ($allCancelledPrs as $code => $id) {
                $targetPrIds->push($id);
                $scanReasons[$id] = 'Tất cả sản phẩm trong PR đã bị hủy';
            }
        }

        // 7. Quét PR trong thùng rác
        if ($cleanTrashed) {
            $trashedPrs = SaleOrderRequest::onlyTrashed()->pluck('id', 'code');
            foreach ($trashedPrs as $code => $id) {
                $targetPrIds->push($id);
                $scanReasons[$id] = 'PR trong thùng rác';
            }
        }

        $targetPrIds = $targetPrIds->unique()->values();

        // Kiểm tra PR items mồ côi (không thuộc PR nào)
        $orphanedItemsCount = SaleOrderRequestItem::whereDoesntHave('saleOrderRequest')->count();

        if ($targetPrIds->isEmpty() && $orphanedItemsCount === 0) {
            $this->info('✨ Hệ thống sạch sẽ! Không tìm thấy Yêu cầu đặt hàng (PR) nào bị lỗi hoặc bị dư.');
            return self::SUCCESS;
        }

        // Hiển thị báo cáo kết quả quét
        $this->warn("🔍 ĐÃ TÌM THẤY: {$targetPrIds->count()} Yêu cầu đặt hàng (PR) cần xử lý & {$orphanedItemsCount} sản phẩm mồ côi.");
        $this->newLine();

        if ($targetPrIds->isNotEmpty()) {
            $prsToDisplay = SaleOrderRequest::withTrashed()
                ->whereIn('id', $targetPrIds)
                ->with(['sale', 'creator'])
                ->get();

            $tableRows = [];
            foreach ($prsToDisplay as $pr) {
                $tableRows[] = [
                    'ID' => $pr->id,
                    'Mã PR' => $pr->code,
                    'Đơn bán (SO)' => $pr->sale?->code ?? 'N/A (Đã xóa)',
                    'Người tạo' => $pr->creator?->name ?? 'N/A',
                    'Trạng thái' => $pr->status_label ?? $pr->status,
                    'Số SP' => $pr->items()->count(),
                    'Lý do dọn dẹp' => $scanReasons[$pr->id] ?? 'Lỗi / Dư thừa',
                ];
            }

            $this->table(['ID', 'Mã PR', 'Đơn bán (SO)', 'Người tạo', 'Trạng thái', 'Số SP', 'Lý do dọn dẹp'], $tableRows);
        }

        if ($dryRun) {
            $this->info('💡 Chế độ --dry-run: Chỉ kiểm tra, không có dữ liệu nào bị thay đổi.');
            return self::SUCCESS;
        }

        // Xác nhận trước khi xóa
        if (!$force) {
            $confirm = $this->readConsoleInput("Bạn có chắc chắn muốn XÓA SẠCH {$targetPrIds->count()} PR này cùng các dữ liệu liên quan? (yes/no) [no]: ");
            if (!in_array(strtolower(trim($confirm)), ['yes', 'y'])) {
                $this->warn('❌ Đã hủy thao tác.');
                return self::SUCCESS;
            }
        }

        $this->info('🔄 Đang tiến hành làm sạch dữ liệu...');

        DB::beginTransaction();
        try {
            $deletedPrCount = 0;
            $deletedItemCount = 0;
            $deletedAttachmentCount = 0;

            if ($targetPrIds->isNotEmpty()) {
                foreach ($targetPrIds as $prId) {
                    $pr = SaleOrderRequest::withTrashed()->find($prId);
                    if (!$pr) {
                        continue;
                    }

                    // 1. Gỡ liên kết trong PurchaseOrderItem để tránh foreign key constraint
                    $itemIds = $pr->items()->pluck('id');
                    if ($itemIds->isNotEmpty()) {
                        // Nếu PO item nằm trong PO draft, xóa luôn PO item
                        PurchaseOrderItem::whereIn('sale_order_request_item_id', $itemIds)
                            ->whereHas('purchaseOrder', fn($q) => $q->where('status', 'draft'))
                            ->delete();

                        // Nếu PO item nằm trong PO khác, set null
                        PurchaseOrderItem::whereIn('sale_order_request_item_id', $itemIds)
                            ->update(['sale_order_request_item_id' => null]);

                        // Xóa các items của PR
                        $deletedItemCount += $pr->items()->delete();
                    }

                    // 2. Xóa các file đính kèm và thư mục lưu trữ
                    $attachments = $pr->attachments;
                    foreach ($attachments as $att) {
                        if ($att->file_path && Storage::disk('public')->exists($att->file_path)) {
                            Storage::disk('public')->delete($att->file_path);
                        }
                    }
                    Storage::disk('public')->deleteDirectory('sale-order-requests/' . $pr->id);
                    $deletedAttachmentCount += $pr->attachments()->delete();

                    // 3. Xóa vĩnh viễn PR
                    $pr->forceDelete();
                    $deletedPrCount++;
                }
            }

            // 4. Xóa các items mồ côi nếu có
            if ($orphanedItemsCount > 0) {
                PurchaseOrderItem::whereNotNull('sale_order_request_item_id')
                    ->whereDoesntHave('saleOrderRequestItem')
                    ->update(['sale_order_request_item_id' => null]);

                $deletedOrphanCount = SaleOrderRequestItem::whereDoesntHave('saleOrderRequest')->delete();
                $deletedItemCount += $deletedOrphanCount;
            }

            DB::commit();

            $this->newLine();
            $this->info('================================================================');
            $this->info('               KẾT QUẢ DỌN DẸP DỮ LIỆU THÀNH CÔNG               ');
            $this->info('================================================================');
            $this->line("  ✅ Đã xóa vĩnh viễn: {$deletedPrCount} Yêu cầu đặt hàng (PR).");
            $this->line("  ✅ Đã dọn sạch:      {$deletedItemCount} sản phẩm (items).");
            $this->line("  ✅ Đã dọn sạch:      {$deletedAttachmentCount} file đính kèm.");
            $this->info('================================================================');

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CleanOrderRequests command error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->error('❌ Lỗi khi dọn dẹp dữ liệu: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Đọc input từ console chuẩn xác trên cả Windows & Linux
     */
    private function readConsoleInput(string $prompt): string
    {
        $this->output->write($prompt);
        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        fclose($handle);
        return trim((string)$line);
    }
}

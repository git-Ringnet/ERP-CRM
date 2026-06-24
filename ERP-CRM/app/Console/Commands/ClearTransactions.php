<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ClearTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:clear
                            {--date= : Ngày loại trừ không xóa (định dạng Y-m-d, ví dụ: 2026-06-24, mặc định là hôm nay)}
                            {--force : Bỏ qua các bước xác nhận bảo mật}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa toàn bộ dữ liệu giao dịch (Xuất kho, Nhập kho, Báo giá, Đơn hàng bán, Đặt hàng hãng PO) ngoại trừ ngày được chỉ định';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->warn('           CÔNG CỤ LÀM SẠCH DỮ LIỆU GIAO DỊCH HỆ THỐNG');
        $this->info('================================================================');

        $date = $this->option('date') ?: date('Y-m-d');

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error('❌ Định dạng ngày không hợp lệ. Vui lòng sử dụng định dạng Y-m-d (ví dụ: 2026-06-24).');
            return self::FAILURE;
        }

        $this->comment("📅 Ngày loại trừ không xóa: {$date}");
        $this->newLine();

        // Count records to be deleted
        $quotationCount = DB::table('quotations')->whereDate('date', '!=', $date)->count();
        $saleCount = DB::table('sales')->whereDate('date', '!=', $date)->count();
        $poCount = DB::table('purchase_orders')->whereDate('order_date', '!=', $date)->count();
        $importCount = DB::table('imports')->whereDate('date', '!=', $date)->count();
        $exportCount = DB::table('exports')->whereDate('date', '!=', $date)->count();
        $prCount = DB::table('sale_order_requests')->whereDate('created_at', '!=', $date)->count();

        $totalRecords = $quotationCount + $saleCount + $poCount + $importCount + $exportCount + $prCount;

        if ($totalRecords === 0) {
            $this->info("✅ Không có dữ liệu giao dịch nào cần xóa (tất cả đều thuộc ngày {$date} hoặc hệ thống trống).");
            return self::SUCCESS;
        }

        $this->warn('⚠ CẢNH BÁO: Toàn bộ dữ liệu của các phân hệ sau (ngoại trừ ngày ' . $date . ') sẽ bị XÓA HOÀN TOÀN:');
        
        $headers = ['Phân hệ / Chứng từ', 'Số lượng bản ghi sẽ xóa', 'Các bảng liên quan bị ảnh hưởng'];
        $rows = [
            ['Báo giá (Quotations)', $quotationCount, 'quotations, quotation_items'],
            ['Đơn hàng bán (Sales)', $saleCount, 'sales, sale_items, sale_expenses, sale_attachments, pnl_approval_attachments, payment_histories, sales_revenues, approval_histories, sale_order_requests, sale_order_request_items, sale_order_request_attachments'],
            ['Đặt hàng hãng (POs)', $poCount, 'purchase_orders, purchase_order_items, supplier_payment_histories, shipping_allocations, shipping_allocation_items'],
            ['Nhập kho (Imports)', $importCount, 'imports, import_items'],
            ['Xuất kho (Exports)', $exportCount, 'exports, export_items'],
            ['Duyệt yêu cầu & Gom đơn (PRs)', $prCount, 'sale_order_requests, sale_order_request_items, sale_order_request_attachments'],
        ];
        $this->table($headers, $rows);
        $this->newLine();

        // Safety checks for production environment
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ CẢNH BÁO: Bạn đang chạy trên môi trường PRODUCTION! Thao tác này cực kỳ nguy hiểm.');
            $confirmProd = $this->readConsoleInput('Gõ chính xác tên môi trường "production" để tiếp tục: ');
            if (trim($confirmProd) !== 'production') {
                $this->warn('❌ Đã hủy thao tác để đảm bảo an toàn.');
                return self::SUCCESS;
            }
        }

        // Double confirmation for safety
        if (!$this->option('force')) {
            $confirm1 = $this->readConsoleInput("Bạn có chắc chắn muốn xóa {$totalRecords} bản ghi giao dịch trên không? (yes/no) [no]: ");
            if (!in_array(strtolower(trim($confirm1)), ['yes', 'y'])) {
                $this->warn('❌ Đã hủy thao tác.');
                return self::SUCCESS;
            }

            $confirm2 = $this->readConsoleInput('CẢNH BÁO LẦN CUỐI: Hành động này KHÔNG THỂ HOÀN TÁC! Nhập "yes" để xác nhận chắc chắn: ');
            if (strtolower(trim($confirm2)) !== 'yes') {
                $this->warn('❌ Đã hủy thao tác.');
                return self::SUCCESS;
            }
        }

        $this->info('🔄 Đang bắt đầu quá trình làm sạch dữ liệu giao dịch...');

        DB::beginTransaction();
        try {
            // Disable foreign key checks
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            // 1. Xóa phiếu xuất kho (Exports)
            if ($exportCount > 0) {
                $this->comment('-> Đang xóa dữ liệu Xuất kho...');
                $exportIds = DB::table('exports')->whereDate('date', '!=', $date)->pluck('id')->toArray();
                DB::table('export_items')->whereIn('export_id', $exportIds)->delete();
                DB::table('exports')->whereIn('id', $exportIds)->delete();
                $this->info("   ✅ Đã xóa {$exportCount} phiếu xuất kho và các mặt hàng liên quan.");
            }

            // 2. Xóa phiếu nhập kho (Imports)
            if ($importCount > 0) {
                $this->comment('-> Đang xóa dữ liệu Nhập kho...');
                $importIds = DB::table('imports')->whereDate('date', '!=', $date)->pluck('id')->toArray();
                DB::table('import_items')->whereIn('import_id', $importIds)->delete();
                DB::table('imports')->whereIn('id', $importIds)->delete();
                $this->info("   ✅ Đã xóa {$importCount} phiếu nhập kho và các mặt hàng liên quan.");
            }

            // 3. Xóa Báo giá (Quotations)
            if ($quotationCount > 0) {
                $this->comment('-> Đang xóa dữ liệu Báo giá...');
                $quotationIds = DB::table('quotations')->whereDate('date', '!=', $date)->pluck('id')->toArray();
                DB::table('quotation_items')->whereIn('quotation_id', $quotationIds)->delete();
                DB::table('quotations')->whereIn('id', $quotationIds)->delete();
                $this->info("   ✅ Đã xóa {$quotationCount} báo giá và các mặt hàng liên quan.");
            }

            // 4. Xóa Đặt hàng hãng (Purchase Orders - PO)
            if ($poCount > 0) {
                $this->comment('-> Đang xóa dữ liệu Đặt hàng hãng (PO)...');
                $poIds = DB::table('purchase_orders')->whereDate('order_date', '!=', $date)->pluck('id')->toArray();
                
                // Xóa items
                DB::table('purchase_order_items')->whereIn('purchase_order_id', $poIds)->delete();
                
                // Xóa lịch sử thanh toán nhà cung cấp
                DB::table('supplier_payment_histories')->whereIn('purchase_order_id', $poIds)->delete();
                
                // Xóa phân bổ vận chuyển liên kết
                $saIds = DB::table('shipping_allocations')->whereIn('purchase_order_id', $poIds)->pluck('id')->toArray();
                if (count($saIds) > 0) {
                    DB::table('shipping_allocation_items')->whereIn('shipping_allocation_id', $saIds)->delete();
                    DB::table('shipping_allocations')->whereIn('id', $saIds)->delete();
                }
                
                // Gỡ liên kết PO ở các phiếu nhập kho còn lại (nếu có)
                DB::table('imports')->where('reference_type', 'purchase_order')->whereIn('reference_id', $poIds)->update([
                    'reference_type' => null,
                    'reference_id' => null
                ]);
                
                // Xóa PO chính
                DB::table('purchase_orders')->whereIn('id', $poIds)->delete();
                $this->info("   ✅ Đã xóa {$poCount} đơn đặt hàng PO và các dữ liệu phụ thuộc.");
            }

            // 5. Xóa Đơn hàng bán (Sales)
            if ($saleCount > 0) {
                $this->comment('-> Đang xóa dữ liệu Đơn hàng bán...');
                $saleIds = DB::table('sales')->whereDate('date', '!=', $date)->pluck('id')->toArray();
                
                // Xóa các bảng phụ thuộc trực tiếp
                DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
                DB::table('sale_expenses')->whereIn('sale_id', $saleIds)->delete();
                DB::table('sale_attachments')->whereIn('sale_id', $saleIds)->delete();
                DB::table('pnl_approval_attachments')->whereIn('sale_id', $saleIds)->delete();
                DB::table('payment_histories')->whereIn('sale_id', $saleIds)->delete();
                DB::table('sales_revenues')->whereIn('sale_id', $saleIds)->delete();
                
                // Xóa lịch sử duyệt PNL
                DB::table('approval_histories')
                    ->where('document_type', 'sale_pnl')
                    ->whereIn('document_id', $saleIds)
                    ->delete();
                    
                // Xóa yêu cầu đặt hàng (PR - Sale Order Requests) và items/tệp đính kèm liên quan
                $sorIds = DB::table('sale_order_requests')->whereIn('sale_id', $saleIds)->pluck('id')->toArray();
                if (count($sorIds) > 0) {
                    DB::table('sale_order_request_items')->whereIn('sale_order_request_id', $sorIds)->delete();
                    DB::table('sale_order_request_attachments')->whereIn('sale_order_request_id', $sorIds)->delete();
                    DB::table('sale_order_requests')->whereIn('id', $sorIds)->delete();
                }
                
                // Gỡ liên kết sale_id ở báo giá và đơn PO còn lại
                DB::table('quotations')->whereIn('converted_to_sale_id', $saleIds)->update(['converted_to_sale_id' => null]);
                DB::table('purchase_orders')->whereIn('sale_id', $saleIds)->update(['sale_id' => null]);
                
                // Xóa tệp đính kèm vật lý trên disk
                foreach ($saleIds as $saleId) {
                    Storage::disk('public')->deleteDirectory('sale-attachments/' . $saleId);
                }
                foreach ($sorIds as $sorId) {
                    Storage::disk('public')->deleteDirectory('sale-order-requests/' . $sorId);
                }
                
                // Xóa đơn hàng bán chính
                DB::table('sales')->whereIn('id', $saleIds)->delete();
                $this->info("   ✅ Đã xóa {$saleCount} đơn hàng bán và các dữ liệu phụ thuộc.");
            }

            // 6. Xóa Duyệt yêu cầu & Gom đơn (PRs)
            if ($prCount > 0) {
                $this->comment('-> Đang xóa dữ liệu Duyệt yêu cầu & Gom đơn (PR)...');
                $prIds = DB::table('sale_order_requests')->whereDate('created_at', '!=', $date)->pluck('id')->toArray();
                
                $prItemIds = DB::table('sale_order_request_items')->whereIn('sale_order_request_id', $prIds)->pluck('id')->toArray();
                if (count($prItemIds) > 0) {
                    DB::table('purchase_order_items')->whereIn('sale_order_request_item_id', $prItemIds)->update(['sale_order_request_item_id' => null]);
                    DB::table('sale_order_request_items')->whereIn('id', $prItemIds)->delete();
                }
                
                DB::table('sale_order_request_attachments')->whereIn('sale_order_request_id', $prIds)->delete();
                DB::table('sale_order_requests')->whereIn('id', $prIds)->delete();
                
                foreach ($prIds as $prId) {
                    Storage::disk('public')->deleteDirectory('sale-order-requests/' . $prId);
                }
                $this->info("   ✅ Đã xóa {$prCount} yêu cầu đặt hàng PR và các dữ liệu liên quan.");
            }

            DB::commit();
            
            $this->newLine();
            $this->info("🎉 HOÀN THÀNH: Đã làm sạch toàn bộ dữ liệu giao dịch trước ngày {$date} thành công!");
            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('❌ CÓ LỖI XẢY RA: ' . $e->getMessage());
            Log::error('ClearTransactions command failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        } finally {
            // Re-enable foreign key checks
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
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

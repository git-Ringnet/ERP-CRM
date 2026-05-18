<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ClearPurchasingData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'purchasing:clear {--force : Bỏ qua bước xác nhận trực tiếp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa toàn bộ dữ liệu phân hệ Mua hàng (PR, Gom đơn, PO, Nhập kho liên quan, Công nợ NCC, Phân bổ vận chuyển)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->warn('⚠ CẢNH BÁO: LỆNH NÀY SẼ XÓA TOÀN BỘ DỮ LIỆU CÁC MODULE SAU:');
        $this->line('  1. Duyệt yêu cầu (PR - Sale Order Requests, Items, Attachments)');
        $this->line('  2. Gom đơn cần đặt (Liên kết PR - PO)');
        $this->line('  3. Đặt hàng với hãng (PO - Purchase Orders, Items, Payments)');
        $this->line('  4. Nhập kho từ PO (Imports & Import Items liên quan đến PO)');
        $this->line('  5. Phân bổ chi phí vận chuyển PO (Shipping Allocations & Items)');
        $this->line('  6. Các thông báo (Notifications) liên quan đến Mua hàng');
        $this->info('================================================================');
        $this->newLine();

        // Safety check for production environment
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ Lệnh này đang chạy trên môi trường PRODUCTION! Vui lòng sử dụng thêm option --force nếu bạn chắc chắn.');
            return self::FAILURE;
        }

        // Ask for manual confirmation using raw low-level PHP stdin reader
        if (!$this->option('force')) {
            $this->output->write('<info>Bạn có chắc chắn muốn xóa sạch toàn bộ dữ liệu phân hệ Mua hàng không? (yes/no) [no]: </info>');
            $confirm1 = '';
            if (PHP_SAPI === 'cli') {
                $handle = @fopen("php://stdin", "r");
                if ($handle) {
                    $confirm1 = fgets($handle);
                    @fclose($handle);
                }
            }
            if (!in_array(strtolower(trim($confirm1)), ['yes', 'y'])) {
                $this->warn('❌ Đã hủy thao tác xóa dữ liệu.');
                return self::SUCCESS;
            }

            // Double confirmation for extreme safety
            $this->output->write('<info>CẢNH BÁO LẦN 2: Hành động này KHÔNG THỂ HOÀN TÁC! Bạn có chắc chắn 100% không? (yes/no) [no]: </info>');
            $confirm2 = '';
            if (PHP_SAPI === 'cli') {
                $handle = @fopen("php://stdin", "r");
                if ($handle) {
                    $confirm2 = fgets($handle);
                    @fclose($handle);
                }
            }
            if (!in_array(strtolower(trim($confirm2)), ['yes', 'y'])) {
                $this->warn('❌ Đã hủy thao tác xóa dữ liệu.');
                return self::SUCCESS;
            }
        }

        $this->info('🔄 Đang bắt đầu quá trình làm sạch dữ liệu Mua hàng...');

        DB::beginTransaction();
        try {
            // Disable foreign key checks to prevent cascade / constraint failures
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            }

            // 1. Phân bổ vận chuyển (Shipping Allocations)
            $this->comment('-> Đang xóa dữ liệu Phân bổ chi phí vận chuyển...');
            $shippingAllocationCount = DB::table('shipping_allocations')->count();
            DB::table('shipping_allocation_items')->delete();
            DB::table('shipping_allocations')->delete();
            $this->info("   ✅ Đã xóa {$shippingAllocationCount} bản ghi phân bổ vận chuyển.");

            // 2. Lịch sử thanh toán công nợ nhà cung cấp (Supplier Payment History)
            $this->comment('-> Đang xóa dữ liệu Lịch sử thanh toán công nợ...');
            $paymentCount = DB::table('supplier_payment_histories')->count();
            DB::table('supplier_payment_histories')->delete();
            $this->info("   ✅ Đã xóa {$paymentCount} bản ghi lịch sử thanh toán.");

            // 3. Đơn đặt hàng PO (Purchase Orders) & Items
            $this->comment('-> Đang xóa dữ liệu Đơn đặt hàng (PO)...');
            $poCount = DB::table('purchase_orders')->count();
            DB::table('purchase_order_items')->delete();
            DB::table('purchase_orders')->delete();
            $this->info("   ✅ Đã xóa {$poCount} đơn đặt hàng PO.");

            // 4. Nhập kho từ PO (Warehouse Imports & Import Items)
            $this->comment('-> Đang xóa chứng từ Nhập kho liên quan đến PO...');
            $poImports = DB::table('imports')->where('reference_type', 'purchase_order')->get();
            $poImportIds = $poImports->pluck('id')->toArray();
            
            $poImportCount = count($poImportIds);
            if ($poImportCount > 0) {
                DB::table('import_items')->whereIn('import_id', $poImportIds)->delete();
                DB::table('imports')->whereIn('id', $poImportIds)->delete();
            }
            $this->info("   ✅ Đã xóa {$poImportCount} phiếu nhập kho liên kết với PO.");

            // 5. Yêu cầu đặt hàng PR (Sale Order Requests) & Items & Attachments
            $this->comment('-> Đang xóa dữ liệu Yêu cầu đặt hàng (PR)...');
            $prCount = DB::table('sale_order_requests')->count();
            DB::table('sale_order_request_attachments')->delete();
            DB::table('sale_order_request_items')->delete();
            DB::table('sale_order_requests')->delete();
            $this->info("   ✅ Đã xóa {$prCount} yêu cầu đặt hàng PR.");

            // 6. Thông báo liên quan (Notifications)
            $this->comment('-> Đang xóa các thông báo liên quan đến mua hàng...');
            $notificationCount = DB::table('notifications')
                ->whereIn('type', ['po_update', 'order_request_approved', 'order_request_rejected'])
                ->delete();
            $this->info("   ✅ Đã xóa {$notificationCount} thông báo liên quan.");

            DB::commit();
            $this->newLine();
            $this->info('🎉 HOÀN THÀNH: Đã làm sạch toàn bộ dữ liệu phân hệ Mua hàng thành công!');
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('❌ CÓ LỖI XẢY RA: ' . $e->getMessage());
            Log::error('ClearPurchasingData command failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        } finally {
            // Enable foreign key checks back
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }
}

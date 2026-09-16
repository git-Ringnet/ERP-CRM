<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseRequestSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $suppliers = Supplier::all();
        $user = User::first();

        if ($products->isEmpty() || $suppliers->isEmpty()) {
            $this->command->warn('Cần có sản phẩm và nhà cung cấp trước.');
            return;
        }

        $statuses = ['draft', 'sent', 'received', 'converted', 'cancelled'];
        $priorities = ['normal', 'high', 'urgent'];
        $lastNum = (int) PurchaseRequest::count();
        
        $sampleTitles = [
            'Yêu cầu mua sắm thiết bị Switch Core Cisco Catalyst 9300 cho Dự án Ngân hàng',
            'Yêu cầu đặt mua License FortiCare 24x7 và FortiGuard Enterprise 3 năm',
            'Đề xuất mua sắm Máy chủ Server Dell PowerEdge R750xs cho Trung tâm dữ liệu',
            'Mua sắm thiết bị tường lửa Firewall FortiGate 100F và Module Quang 10G SFP+',
            'Mua sắm linh kiện nâng cấp RAM 64GB & NVMe Enterprise cho Hệ thống Cloud',
            'Yêu cầu mua sắm thiết bị Access Point Wifi 6 Ruijie Reyee cho Tòa nhà Văn phòng',
            'Đề xuất mua bản quyền Microsoft 365 E5 và Windows Server Datacenter 2022',
            'Mua sắm bộ lưu điện UPS APC Smart-UPS Online 10kVA cho Phòng Server IDF',
            'Yêu cầu báo giá thiết bị cân bằng tải Load Balancer Array Networks',
            'Yêu cầu mua sắm giải pháp Backup & Replication Veeam Availability Suite',
        ];

        // Tạo 10 yêu cầu báo giá NCC
        for ($i = 0; $i < count($sampleTitles); $i++) {
            $code = 'PR-' . date('Ymd') . '-' . str_pad($lastNum + $i + 1, 4, '0', STR_PAD_LEFT);
            if (PurchaseRequest::where('code', $code)->exists()) {
                continue;
            }

            $title = $sampleTitles[$i];
            $status = $statuses[array_rand($statuses)];
            
            $request = PurchaseRequest::create([
                'code' => $code,
                'title' => $title,
                'deadline' => now()->addDays(rand(7, 30)),
                'priority' => $priorities[array_rand($priorities)],
                'status' => $status,
                'requirements' => "Yêu cầu báo giá chi tiết, bao gồm:\n- Đơn giá CIF / DDP TP.HCM (chưa VAT và có VAT)\n- Thời gian giao hàng cam kết (Lead time)\n- Điều khoản thanh toán (T/T 30 ngày)\n- Thời hạn bảo hành chính hãng từ nhà sản xuất (CO/CQ)",
                'note' => "Hồ sơ dự án trọng điểm, ưu tiên phản hồi trước ngày hạn chót.",
                'created_by' => $user?->id,
                'sent_at' => in_array($status, ['sent', 'received', 'converted']) ? now()->subDays(rand(1, 10)) : null,
            ]);

            // Thêm 2-3 nhà cung cấp cho mỗi yêu cầu
            $supplierCount = min(rand(2, 3), $suppliers->count());
            $selectedSuppliers = $suppliers->random($supplierCount);
            
            foreach ($selectedSuppliers as $supplier) {
                DB::table('purchase_request_suppliers')->insert([
                    'purchase_request_id' => $request->id,
                    'supplier_id' => $supplier->id,
                    'sent_at' => in_array($status, ['sent', 'received', 'converted']) ? now()->subDays(rand(1, 5)) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Thêm 2-4 sản phẩm cho mỗi yêu cầu
            $itemCount = min(rand(2, 4), $products->count());
            $selectedProducts = $products->random($itemCount);

            foreach ($selectedProducts as $product) {
                PurchaseRequestItem::create([
                    'purchase_request_id' => $request->id,
                    'product_id' => $product->id,
                    'product_name' => Str::limit($product->name, 190),
                    'quantity' => rand(5, 50),
                    'unit' => $product->unit ?? 'Cái',
                    'specifications' => rand(0, 1) ? 'Hàng chính hãng, có bảo hành' : null,
                ]);
            }
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu Yêu cầu mua hàng (Purchase Requests)!');
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $customers = DB::table('customers')->get();
        $products = DB::table('products')->get();
        $users = DB::table('users')->first();
        $userId = $users ? $users->id : 1;

        if ($customers->isEmpty()) {
            // Tạo khách hàng mặc định nếu chưa có
            $custId = DB::table('customers')->insertGetId([
                'code' => 'KH-DEFAULT',
                'name' => 'Công ty TNHH Giải Pháp Công Nghệ',
                'type' => 'si',
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $customers = DB::table('customers')->where('id', $custId)->get();
        }

        $cCount = $customers->count();
        $cust = fn($idx) => $customers[$idx % $cCount];
        
        $quotations = [
            ['code' => 'QT-2026-0001', 'customer_id' => $cust(0)->id, 'customer_name' => $cust(0)->name ?? 'Công ty TNHH ABC', 'title' => '🏢 Giải pháp Bảo mật Mạng Toàn diện 2026', 'date' => Carbon::now()->subDays(15), 'valid_until' => Carbon::now()->addDays(15), 'subtotal' => 685000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 715825000, 'payment_terms' => 'Thanh toán 50% khi ký hợp đồng, 50% khi hoàn thành', 'delivery_time' => '15-20 ngày làm việc', 'note' => 'Bao gồm cài đặt, cấu hình và đào tạo miễn phí', 'status' => 'approved', 'current_approval_level' => 2, 'created_by' => $userId],
            ['code' => 'QT-2026-0002', 'customer_id' => $cust(1)->id, 'customer_name' => $cust(1)->name ?? 'Công ty CP XYZ', 'title' => '💻 Trang bị Phòng máy Cao cấp - Chi nhánh mới', 'date' => Carbon::now()->subDays(10), 'valid_until' => Carbon::now()->addDays(20), 'subtotal' => 258500000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 255915000, 'payment_terms' => 'Thanh toán theo tiến độ dự án', 'delivery_time' => '7-10 ngày làm việc', 'note' => 'Hỗ trợ bảo hành tận nơi 24/7', 'status' => 'sent', 'current_approval_level' => 2, 'created_by' => $userId],
            ['code' => 'QT-2026-0003', 'customer_id' => $cust(2)->id, 'customer_name' => $cust(2)->name ?? 'Cửa hàng Minh Phát', 'title' => '🖱️ Phụ kiện Máy tính Bán lẻ', 'date' => Carbon::now()->subDays(5), 'valid_until' => Carbon::now()->addDays(25), 'subtotal' => 45800000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 47861000, 'payment_terms' => 'Thanh toán 100% khi giao hàng', 'delivery_time' => '3-5 ngày làm việc', 'note' => 'Giá đã bao gồm vận chuyển nội thành', 'status' => 'accepted', 'current_approval_level' => 1, 'created_by' => $userId],
            ['code' => 'QT-2026-0004', 'customer_id' => $cust(3)->id, 'customer_name' => $cust(3)->name ?? 'Siêu thị Đại Việt', 'title' => '🔐 Hệ thống Firewall & WiFi Doanh nghiệp', 'date' => Carbon::now()->subDays(3), 'valid_until' => Carbon::now()->addDays(27), 'subtotal' => 892000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 883080000, 'payment_terms' => 'Thanh toán 30% ký HĐ, 40% giao hàng, 30% nghiệm thu', 'delivery_time' => '25-30 ngày làm việc', 'note' => 'Dự án trọng điểm - Ưu tiên triển khai nhanh', 'status' => 'pending', 'current_approval_level' => 1, 'created_by' => $userId],
            ['code' => 'QT-2026-0005', 'customer_id' => $cust(4)->id, 'customer_name' => $cust(4)->name ?? 'Shop Online Hạnh Phúc', 'title' => '📦 Gói Thiết bị Khởi nghiệp Start-up', 'date' => Carbon::now()->subDays(20), 'valid_until' => Carbon::now()->subDays(5), 'subtotal' => 35500000, 'discount' => 0.00, 'vat' => 10.00, 'total' => 39050000, 'payment_terms' => 'COD khi nhận hàng', 'delivery_time' => '2-3 ngày làm việc', 'note' => 'Hỗ trợ trả góp 0% qua thẻ tín dụng', 'status' => 'expired', 'current_approval_level' => 1, 'created_by' => $userId],
        ];
        
        $prices = [
            'SP001' => 18500000, 'SP002' => 450000, 'SP003' => 2500000, 'SP004' => 125000000,
            'SP005' => 285000000, 'SP006' => 5200000, 'SP007' => 45000000, 'SP008' => 18500000,
        ];
        
        foreach ($quotations as $quotation) {
            $quotation['created_at'] = $now;
            $quotation['updated_at'] = $now;
            
            // Dùng updateOrCreate để tránh trùng lặp mã code
            $existing = DB::table('quotations')->where('code', $quotation['code'])->first();
            if ($existing) {
                $quotationId = $existing->id;
                DB::table('quotations')->where('id', $quotationId)->update($quotation);
            } else {
                $quotationId = DB::table('quotations')->insertGetId($quotation);
            }
            
            // Chỉ thêm items nếu có sản phẩm
            if ($products->isNotEmpty()) {
                $randomCount = min(rand(1, 3), $products->count());
                foreach ($products->random($randomCount) as $product) {
                    $qty = rand(1, 10);
                    $price = $prices[$product->code] ?? 1500000;
                    
                    DB::table('quotation_items')->updateOrInsert(
                        ['quotation_id' => $quotationId, 'product_id' => $product->id],
                        [
                            'product_name' => $product->name,
                            'product_code' => $product->code,
                            'quantity' => $qty,
                            'price' => $price,
                            'total' => $qty * $price,
                            'note' => $qty >= 5 ? 'Áp dụng giá sỉ đặc biệt' : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu Báo giá (Quotations)!');
    }
}

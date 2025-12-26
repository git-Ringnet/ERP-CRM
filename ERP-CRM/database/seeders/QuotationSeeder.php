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

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Cần có khách hàng và sản phẩm trước.');
            return;
        }
        
        // discount và vat là % (decimal 5,2)
        $quotations = [
            ['code' => 'QT-2024-0001', 'customer_id' => $customers->first()?->id, 'customer_name' => 'Công ty TNHH ABC', 'title' => '🏢 Giải pháp Bảo mật Mạng Toàn diện 2024', 'date' => Carbon::now()->subDays(15), 'valid_until' => Carbon::now()->addDays(15), 'subtotal' => 685000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 715825000, 'payment_terms' => 'Thanh toán 50% khi ký hợp đồng, 50% khi hoàn thành', 'delivery_time' => '15-20 ngày làm việc', 'note' => 'Bao gồm cài đặt, cấu hình và đào tạo miễn phí', 'status' => 'approved', 'current_approval_level' => 2, 'created_by' => $users?->id],
            ['code' => 'QT-2024-0002', 'customer_id' => $customers->skip(1)->first()?->id, 'customer_name' => 'Công ty CP XYZ', 'title' => '💻 Trang bị Phòng máy Cao cấp - Chi nhánh mới', 'date' => Carbon::now()->subDays(10), 'valid_until' => Carbon::now()->addDays(20), 'subtotal' => 258500000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 255915000, 'payment_terms' => 'Thanh toán theo tiến độ dự án', 'delivery_time' => '7-10 ngày làm việc', 'note' => 'Hỗ trợ bảo hành tận nơi 24/7', 'status' => 'sent', 'current_approval_level' => 2, 'created_by' => $users?->id],
            ['code' => 'QT-2024-0003', 'customer_id' => $customers->skip(2)->first()?->id, 'customer_name' => 'Cửa hàng Minh Phát', 'title' => '🖱️ Phụ kiện Máy tính Bán lẻ', 'date' => Carbon::now()->subDays(5), 'valid_until' => Carbon::now()->addDays(25), 'subtotal' => 45800000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 47861000, 'payment_terms' => 'Thanh toán 100% khi giao hàng', 'delivery_time' => '3-5 ngày làm việc', 'note' => 'Giá đã bao gồm vận chuyển nội thành', 'status' => 'accepted', 'current_approval_level' => 1, 'created_by' => $users?->id],
            ['code' => 'QT-2024-0004', 'customer_id' => $customers->skip(3)->first()?->id, 'customer_name' => 'Siêu thị Đại Việt', 'title' => '🔐 Hệ thống Firewall & WiFi Doanh nghiệp', 'date' => Carbon::now()->subDays(3), 'valid_until' => Carbon::now()->addDays(27), 'subtotal' => 892000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 883080000, 'payment_terms' => 'Thanh toán 30% ký HĐ, 40% giao hàng, 30% nghiệm thu', 'delivery_time' => '25-30 ngày làm việc', 'note' => 'Dự án trọng điểm - Ưu tiên triển khai nhanh', 'status' => 'pending', 'current_approval_level' => 1, 'created_by' => $users?->id],
            ['code' => 'QT-2024-0005', 'customer_id' => $customers->skip(4)->first()?->id, 'customer_name' => 'Shop Online Hạnh Phúc', 'title' => '📦 Gói Thiết bị Khởi nghiệp Start-up', 'date' => Carbon::now()->subDays(20), 'valid_until' => Carbon::now()->subDays(5), 'subtotal' => 35500000, 'discount' => 0.00, 'vat' => 10.00, 'total' => 39050000, 'payment_terms' => 'COD khi nhận hàng', 'delivery_time' => '2-3 ngày làm việc', 'note' => 'Hỗ trợ trả góp 0% qua thẻ tín dụng', 'status' => 'expired', 'current_approval_level' => 1, 'created_by' => $users?->id],
            ['code' => 'QT-2024-0006', 'customer_id' => $customers->first()?->id, 'customer_name' => 'Công ty TNHH ABC', 'title' => '🌐 Nâng cấp Hạ tầng Mạng Core', 'date' => Carbon::now()->subDays(2), 'valid_until' => Carbon::now()->addDays(28), 'subtotal' => 1250000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 1237500000, 'payment_terms' => 'Theo thỏa thuận hợp đồng năm', 'delivery_time' => '45-60 ngày làm việc', 'note' => 'Dự án chiến lược dài hạn, ưu đãi đặc biệt cho VIP', 'status' => 'draft', 'current_approval_level' => 0, 'created_by' => $users?->id],
            ['code' => 'QT-2024-0007', 'customer_id' => $customers->skip(1)->first()?->id, 'customer_name' => 'Công ty CP XYZ', 'title' => '🖥️ Màn hình Gaming & Thiết bị Ngoại vi', 'date' => Carbon::now()->subDays(25), 'valid_until' => Carbon::now()->subDays(10), 'subtotal' => 78500000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 77715000, 'payment_terms' => 'Thanh toán 100% trước khi giao', 'delivery_time' => '5-7 ngày làm việc', 'note' => 'Khách không phản hồi sau 15 ngày', 'status' => 'declined', 'current_approval_level' => 2, 'created_by' => $users?->id],
            ['code' => 'QT-2024-0008', 'customer_id' => $customers->skip(3)->first()?->id, 'customer_name' => 'Siêu thị Đại Việt', 'title' => '🛡️ Bảo trì & Nâng cấp License FortiGate', 'date' => Carbon::now()->subDays(8), 'valid_until' => Carbon::now()->addDays(22), 'subtotal' => 156000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 154440000, 'payment_terms' => 'Thanh toán trong 30 ngày kể từ ngày ký', 'delivery_time' => 'Kích hoạt license online sau thanh toán', 'note' => 'Gia hạn license 3 năm với giá ưu đãi', 'status' => 'approved', 'current_approval_level' => 2, 'created_by' => $users?->id],
        ];
        
        $prices = [
            'SP001' => 18500000, 'SP002' => 450000, 'SP003' => 2500000, 'SP004' => 125000000,
            'SP005' => 285000000, 'SP006' => 5200000, 'SP007' => 45000000, 'SP008' => 18500000,
        ];
        
        foreach ($quotations as $quotation) {
            $quotation['created_at'] = $now;
            $quotation['updated_at'] = $now;
            $quotationId = DB::table('quotations')->insertGetId($quotation);
            
            // Chỉ thêm items nếu có sản phẩm
            if ($products->count() >= 2) {
                $randomCount = min(rand(2, 4), $products->count());
                foreach ($products->random($randomCount) as $product) {
                    $qty = rand(1, 15);
                    $price = $prices[$product->code] ?? 1000000;
                    DB::table('quotation_items')->insert([
                        'quotation_id' => $quotationId, 'product_id' => $product->id,
                        'product_name' => $product->name, 'product_code' => $product->code,
                        'quantity' => $qty, 'price' => $price, 'total' => $qty * $price,
                        'note' => $qty >= 5 ? 'Áp dụng giá sỉ đặc biệt' : null,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}

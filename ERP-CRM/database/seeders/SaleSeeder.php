<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $customers = DB::table('customers')->get();
        $products = DB::table('products')->get();
        $projects = DB::table('projects')->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Cần có khách hàng và sản phẩm trước. Chạy CustomerSeeder và ProductSeeder trước.');
            return;
        }
        
        // discount và vat là % (decimal 5,2), không phải số tiền
        $sales = [
            ['code' => 'DH-2024-0001', 'type' => 'project', 'project_id' => $projects->first()?->id, 'customer_id' => $customers->first()?->id, 'customer_name' => 'Công ty TNHH ABC', 'date' => Carbon::now()->subDays(30), 'delivery_address' => '720A Điện Biên Phủ, TP.HCM', 'subtotal' => 568000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 593560000, 'cost' => 398920000, 'margin' => 194640000, 'margin_percent' => 32.79, 'paid_amount' => 593560000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'Phase 1 - Hạ tầng mạng Core ✅'],
            ['code' => 'DH-2024-0002', 'type' => 'project', 'project_id' => $projects->first()?->id, 'customer_id' => $customers->first()?->id, 'customer_name' => 'Công ty TNHH ABC', 'date' => Carbon::now()->subDays(15), 'delivery_address' => '720A Điện Biên Phủ, TP.HCM', 'subtotal' => 850000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 888250000, 'cost' => 612800000, 'margin' => 275450000, 'margin_percent' => 31.01, 'paid_amount' => 444125000, 'debt_amount' => 444125000, 'payment_status' => 'partial', 'status' => 'shipping', 'note' => 'Phase 2 - Firewall & WiFi 🚚'],
            ['code' => 'DH-2024-0003', 'type' => 'project', 'project_id' => $projects->skip(1)->first()?->id, 'customer_id' => $customers->skip(1)->first()?->id, 'customer_name' => 'Công ty CP XYZ', 'date' => Carbon::now()->subDays(20), 'delivery_address' => '201 Nguyễn Chí Thanh, Q5, TP.HCM', 'subtotal' => 425000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 444125000, 'cost' => 297500000, 'margin' => 146625000, 'margin_percent' => 33.01, 'paid_amount' => 444125000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'Nâng cấp mạng LAN bệnh viện ✅'],
            ['code' => 'DH-2024-0004', 'type' => 'retail', 'project_id' => null, 'customer_id' => $customers->skip(2)->first()?->id, 'customer_name' => 'Cửa hàng Minh Phát', 'date' => Carbon::now()->subDays(7), 'delivery_address' => '789 Trần Hưng Đạo, Q5, TP.HCM', 'subtotal' => 28500000, 'discount' => 0.00, 'vat' => 10.00, 'total' => 31350000, 'cost' => 22200000, 'margin' => 9150000, 'margin_percent' => 29.19, 'paid_amount' => 31350000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'Laptop & màn hình ⚡'],
            ['code' => 'DH-2024-0005', 'type' => 'retail', 'project_id' => null, 'customer_id' => $customers->skip(4)->first()?->id, 'customer_name' => 'Shop Online Hạnh Phúc', 'date' => Carbon::now()->subDays(5), 'delivery_address' => '654 Võ Văn Tần, Q3, TP.HCM', 'subtotal' => 12850000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 12721500, 'cost' => 9280000, 'margin' => 3441500, 'margin_percent' => 27.05, 'paid_amount' => 0, 'debt_amount' => 12721500, 'payment_status' => 'unpaid', 'status' => 'approved', 'note' => 'Phụ kiện gaming 🎮'],
            ['code' => 'DH-2024-0006', 'type' => 'project', 'project_id' => $projects->skip(2)->first()?->id, 'customer_id' => $customers->skip(3)->first()?->id, 'customer_name' => 'Siêu thị Đại Việt', 'date' => Carbon::now()->subDays(45), 'delivery_address' => 'P.Linh Trung, Thủ Đức, TP.HCM', 'subtotal' => 385000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 381150000, 'cost' => 257950000, 'margin' => 123200000, 'margin_percent' => 32.32, 'paid_amount' => 381150000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'WiFi Campus đại học 🎓'],
            ['code' => 'DH-2024-0007', 'type' => 'retail', 'project_id' => null, 'customer_id' => $customers->first()?->id, 'customer_name' => 'Công ty TNHH ABC', 'date' => Carbon::now()->subDays(3), 'delivery_address' => '123 Nguyễn Huệ, Q1, TP.HCM', 'subtotal' => 156000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 154440000, 'cost' => 109200000, 'margin' => 45240000, 'margin_percent' => 29.30, 'paid_amount' => 0, 'debt_amount' => 154440000, 'payment_status' => 'unpaid', 'status' => 'pending', 'note' => 'Thiết bị bảo mật 🔐'],
            ['code' => 'DH-2024-0008', 'type' => 'project', 'project_id' => $projects->skip(3)->first()?->id, 'customer_id' => $customers->first()?->id, 'customer_name' => 'Công ty TNHH ABC', 'date' => Carbon::now()->subDays(60), 'delivery_address' => 'KCN Công nghệ cao, Q9, TP.HCM', 'subtotal' => 1580000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 1564200000, 'cost' => 1106000000, 'margin' => 458200000, 'margin_percent' => 29.29, 'paid_amount' => 1564200000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'Industry 4.0 🏆'],
            ['code' => 'DH-2024-0009', 'type' => 'retail', 'project_id' => null, 'customer_id' => $customers->skip(1)->first()?->id, 'customer_name' => 'Công ty CP XYZ', 'date' => Carbon::now()->subDays(2), 'delivery_address' => '456 Lê Lợi, Q3, TP.HCM', 'subtotal' => 68500000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 67815000, 'cost' => 48950000, 'margin' => 18865000, 'margin_percent' => 27.82, 'paid_amount' => 20000000, 'debt_amount' => 47815000, 'payment_status' => 'partial', 'status' => 'shipping', 'note' => 'Đang giao hàng 📦'],
            ['code' => 'DH-2024-0010', 'type' => 'project', 'project_id' => $projects->skip(5)->first()?->id, 'customer_id' => $customers->skip(3)->first()?->id, 'customer_name' => 'Siêu thị Đại Việt', 'date' => Carbon::now()->subDays(10), 'delivery_address' => '35 Hàng Vôi, Hoàn Kiếm, Hà Nội', 'subtotal' => 2850000000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 2821500000, 'cost' => 1995000000, 'margin' => 826500000, 'margin_percent' => 29.29, 'paid_amount' => 846450000, 'debt_amount' => 1975050000, 'payment_status' => 'partial', 'status' => 'approved', 'note' => 'SOC Center BIDV 💰'],
        ];
        
        $prices = [
            'SP001' => ['sell' => 18500000, 'cost' => 14800000],
            'SP002' => ['sell' => 450000, 'cost' => 320000],
            'SP003' => ['sell' => 2500000, 'cost' => 1850000],
            'SP004' => ['sell' => 125000000, 'cost' => 87500000],
            'SP005' => ['sell' => 285000000, 'cost' => 199500000],
            'SP006' => ['sell' => 5200000, 'cost' => 3800000],
            'SP007' => ['sell' => 45000000, 'cost' => 31500000],
            'SP008' => ['sell' => 18500000, 'cost' => 12950000],
        ];
        
        foreach ($sales as $sale) {
            $sale['created_at'] = $now;
            $sale['updated_at'] = $now;
            $saleId = DB::table('sales')->insertGetId($sale);
            
            // Chỉ thêm items nếu có sản phẩm
            if ($products->count() >= 2) {
                $randomCount = min(rand(2, 4), $products->count());
                foreach ($products->random($randomCount) as $product) {
                    $qty = rand(1, 8);
                    $p = $prices[$product->code] ?? ['sell' => 1000000, 'cost' => 700000];
                    DB::table('sale_items')->insert([
                        'sale_id' => $saleId, 'product_id' => $product->id, 'product_name' => $product->name,
                        'project_id' => $sale['project_id'], 'quantity' => $qty, 'price' => $p['sell'],
                        'cost_price' => $p['cost'], 'total' => $qty * $p['sell'], 'cost_total' => $qty * $p['cost'],
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
            
            if ($sale['type'] === 'project') {
                DB::table('sale_expenses')->insert([
                    'sale_id' => $saleId, 'type' => 'shipping', 'description' => 'Phí vận chuyển',
                    'amount' => rand(500000, 2000000), 'note' => null, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }
}

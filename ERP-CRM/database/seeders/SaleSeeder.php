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

        if ($customers->isEmpty()) {
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
        $prjId = $projects->isNotEmpty() ? $projects->first()->id : null;
        
        $sales = [
            ['code' => 'DH-2026-0001', 'type' => 'project', 'project_id' => $prjId, 'customer_id' => $cust(0)->id, 'customer_name' => $cust(0)->name ?? 'Công ty TNHH ABC', 'date' => Carbon::now()->subDays(30), 'delivery_address' => '720A Điện Biên Phủ, TP.HCM', 'subtotal' => 568000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 593560000, 'cost' => 398920000, 'margin' => 194640000, 'margin_percent' => 32.79, 'paid_amount' => 593560000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'Phase 1 - Hạ tầng mạng Core ✅'],
            ['code' => 'DH-2026-0002', 'type' => 'project', 'project_id' => $prjId, 'customer_id' => $cust(1)->id, 'customer_name' => $cust(1)->name ?? 'Công ty TNHH ABC', 'date' => Carbon::now()->subDays(15), 'delivery_address' => '720A Điện Biên Phủ, TP.HCM', 'subtotal' => 850000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 888250000, 'cost' => 612800000, 'margin' => 275450000, 'margin_percent' => 31.01, 'paid_amount' => 444125000, 'debt_amount' => 444125000, 'payment_status' => 'partial', 'status' => 'shipping', 'note' => 'Phase 2 - Firewall & WiFi 🚚'],
            ['code' => 'DH-2026-0003', 'type' => 'project', 'project_id' => $prjId, 'customer_id' => $cust(2)->id, 'customer_name' => $cust(2)->name ?? 'Công ty CP XYZ', 'date' => Carbon::now()->subDays(20), 'delivery_address' => '201 Nguyễn Chí Thanh, Q5, TP.HCM', 'subtotal' => 425000000, 'discount' => 5.00, 'vat' => 10.00, 'total' => 444125000, 'cost' => 297500000, 'margin' => 146625000, 'margin_percent' => 33.01, 'paid_amount' => 444125000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'Nâng cấp mạng LAN bệnh viện ✅'],
            ['code' => 'DH-2026-0004', 'type' => 'retail', 'project_id' => null, 'customer_id' => $cust(3)->id, 'customer_name' => $cust(3)->name ?? 'Cửa hàng Minh Phát', 'date' => Carbon::now()->subDays(7), 'delivery_address' => '789 Trần Hưng Đạo, Q5, TP.HCM', 'subtotal' => 28500000, 'discount' => 0.00, 'vat' => 10.00, 'total' => 31350000, 'cost' => 22200000, 'margin' => 9150000, 'margin_percent' => 29.19, 'paid_amount' => 31350000, 'debt_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed', 'note' => 'Laptop & màn hình ⚡'],
            ['code' => 'DH-2026-0005', 'type' => 'retail', 'project_id' => null, 'customer_id' => $cust(4)->id, 'customer_name' => $cust(4)->name ?? 'Shop Online Hạnh Phúc', 'date' => Carbon::now()->subDays(5), 'delivery_address' => '654 Võ Văn Tần, Q3, TP.HCM', 'subtotal' => 12850000, 'discount' => 10.00, 'vat' => 10.00, 'total' => 12721500, 'cost' => 9280000, 'margin' => 3441500, 'margin_percent' => 27.05, 'paid_amount' => 0, 'debt_amount' => 12721500, 'payment_status' => 'unpaid', 'status' => 'approved', 'note' => 'Phụ kiện gaming 🎮'],
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
            
            $existing = DB::table('sales')->where('code', $sale['code'])->first();
            if ($existing) {
                $saleId = $existing->id;
                DB::table('sales')->where('id', $saleId)->update($sale);
            } else {
                $saleId = DB::table('sales')->insertGetId($sale);
            }
            
            // Thêm items nếu có sản phẩm
            if ($products->isNotEmpty()) {
                $randomCount = min(rand(1, 3), $products->count());
                foreach ($products->random($randomCount) as $product) {
                    $qty = rand(1, 8);
                    $p = $prices[$product->code] ?? ['sell' => 1500000, 'cost' => 1000000];
                    
                    DB::table('sale_items')->updateOrInsert(
                        ['sale_id' => $saleId, 'product_id' => $product->id],
                        [
                            'product_name' => $product->name,
                            'project_id' => $sale['project_id'],
                            'quantity' => $qty,
                            'price' => $p['sell'],
                            'cost_price' => $p['cost'],
                            'total' => $qty * $p['sell'],
                            'cost_total' => $qty * $p['cost'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu Đơn bán hàng (Sales)!');
    }
}

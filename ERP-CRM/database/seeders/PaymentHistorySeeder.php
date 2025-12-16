<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentHistorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $customers = DB::table('customers')->get();
        $sales = DB::table('sales')->get();
        
        $payments = [
            // Khách hàng 1 - VIP với nhiều giao dịch
            ['customer_id' => $customers->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0001')->first()?->id, 'amount' => 296780000, 'payment_date' => Carbon::now()->subDays(28), 'payment_method' => 'bank_transfer', 'note' => 'Đặt cọc 50% - Chuyển khoản VCB 💰', 'reference_number' => 'VCB-2024-11-17-001'],
            ['customer_id' => $customers->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0001')->first()?->id, 'amount' => 296780000, 'payment_date' => Carbon::now()->subDays(20), 'payment_method' => 'bank_transfer', 'note' => 'Thanh toán đợt 2 - Hoàn thành ✅', 'reference_number' => 'VCB-2024-11-25-003'],
            ['customer_id' => $customers->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0002')->first()?->id, 'amount' => 444125000, 'payment_date' => Carbon::now()->subDays(12), 'payment_method' => 'bank_transfer', 'note' => 'Tạm ứng 50% theo HĐ 🏦', 'reference_number' => 'TCB-2024-12-03-007'],
            ['customer_id' => $customers->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0008')->first()?->id, 'amount' => 782100000, 'payment_date' => Carbon::now()->subDays(55), 'payment_method' => 'bank_transfer', 'note' => 'Đặt cọc 50% dự án Industry 4.0 🏭', 'reference_number' => 'MB-2024-10-20-015'],
            ['customer_id' => $customers->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0008')->first()?->id, 'amount' => 782100000, 'payment_date' => Carbon::now()->subDays(35), 'payment_method' => 'bank_transfer', 'note' => 'Thanh toán hoàn tất - Samsung Project 🏆', 'reference_number' => 'MB-2024-11-10-022'],

            // Khách hàng 2 - Công ty XYZ
            ['customer_id' => $customers->skip(1)->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0003')->first()?->id, 'amount' => 222062500, 'payment_date' => Carbon::now()->subDays(18), 'payment_method' => 'bank_transfer', 'note' => 'Ứng trước 50% 💳', 'reference_number' => 'ACB-2024-11-27-009'],
            ['customer_id' => $customers->skip(1)->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0003')->first()?->id, 'amount' => 222062500, 'payment_date' => Carbon::now()->subDays(10), 'payment_method' => 'bank_transfer', 'note' => 'Thanh lý hợp đồng ✅', 'reference_number' => 'ACB-2024-12-05-012'],
            ['customer_id' => $customers->skip(1)->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0009')->first()?->id, 'amount' => 20000000, 'payment_date' => Carbon::now()->subDays(1), 'payment_method' => 'cash', 'note' => 'Đặt cọc tiền mặt 💵', 'reference_number' => 'CASH-2024-12-14'],

            // Khách hàng 3 - Cửa hàng Minh Phát
            ['customer_id' => $customers->skip(2)->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0004')->first()?->id, 'amount' => 31350000, 'payment_date' => Carbon::now()->subDays(6), 'payment_method' => 'cash', 'note' => 'Thanh toán COD khi nhận hàng 📦', 'reference_number' => 'COD-2024-12-09'],

            // Khách hàng 4 - Siêu thị Đại Việt (VIP lớn)
            ['customer_id' => $customers->skip(3)->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0006')->first()?->id, 'amount' => 190575000, 'payment_date' => Carbon::now()->subDays(42), 'payment_method' => 'bank_transfer', 'note' => 'Đặt cọc 50% WiFi Campus 🎓', 'reference_number' => 'BIDV-2024-11-03-005'],
            ['customer_id' => $customers->skip(3)->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0006')->first()?->id, 'amount' => 190575000, 'payment_date' => Carbon::now()->subDays(30), 'payment_method' => 'bank_transfer', 'note' => 'Nghiệm thu thành công, thanh toán đợt cuối ✅', 'reference_number' => 'BIDV-2024-11-15-011'],
            ['customer_id' => $customers->skip(3)->first()?->id, 'sale_id' => $sales->where('code', 'DH-2024-0010')->first()?->id, 'amount' => 846450000, 'payment_date' => Carbon::now()->subDays(8), 'payment_method' => 'bank_transfer', 'note' => 'Đặt cọc 30% SOC Center BIDV 🏦💰', 'reference_number' => 'BIDV-2024-12-07-018'],
        ];
        
        foreach ($payments as $payment) {
            if ($payment['sale_id']) {
                $payment['created_at'] = $now;
                $payment['updated_at'] = $now;
                DB::table('payment_histories')->insert($payment);
            }
        }
    }
}

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
        $sales = DB::table('sales')->get();
        
        if ($sales->isEmpty()) {
            $this->command->warn('Cần có đơn bán hàng trước.');
            return;
        }

        foreach ($sales as $sale) {
            if ($sale->paid_amount > 0) {
                $refNumber = 'PAY-' . $sale->code . '-01';
                if (!DB::table('payment_histories')->where('reference_number', $refNumber)->exists()) {
                    DB::table('payment_histories')->insert([
                        'customer_id' => $sale->customer_id,
                        'sale_id' => $sale->id,
                        'amount' => $sale->paid_amount,
                        'payment_date' => Carbon::parse($sale->date)->addDays(rand(1, 5)),
                        'payment_method' => rand(0, 1) ? 'bank_transfer' : 'cash',
                        'note' => 'Thanh toán đợt 1 đơn hàng ' . $sale->code,
                        'reference_number' => $refNumber,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $this->command->info('Đã tạo thành công dữ liệu mẫu Lịch sử thanh toán (Payment Histories)!');
    }
}

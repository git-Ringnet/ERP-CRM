<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostFormulaSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        
        $formulas = [
            [
                'code' => 'CF-001',
                'name' => '🚚 Phí giao hàng nội thành',
                'type' => 'shipping',
                'calculation_type' => 'fixed',
                'fixed_amount' => 150000,
                'percentage' => null,
                'formula' => null,
                'apply_to' => 'all',
                'apply_conditions' => null,
                'is_active' => true,
                'description' => 'Phí giao hàng cố định cho đơn hàng nội thành TP.HCM, áp dụng cho mọi sản phẩm.',
            ],
            [
                'code' => 'CF-002',
                'name' => '📦 Phí giao hàng liên tỉnh',
                'type' => 'shipping',
                'calculation_type' => 'formula',
                'fixed_amount' => 0,
                'percentage' => null,
                'formula' => 'distance * 5000 + weight * 2000',
                'apply_to' => 'all',
                'apply_conditions' => null,
                'is_active' => true,
                'description' => 'Tính phí dựa trên khoảng cách (km) và trọng lượng (kg). VD: 100km, 10kg = 520,000 VND',
            ],
            [
                'code' => 'CF-003',
                'name' => '💼 Hoa hồng Sales Rep',
                'type' => 'commission',
                'calculation_type' => 'percentage',
                'fixed_amount' => null,
                'percentage' => 3.0,
                'formula' => null,
                'apply_to' => 'all',
                'apply_conditions' => null,
                'is_active' => true,
                'description' => 'Hoa hồng 3% trên doanh thu cho nhân viên kinh doanh. Áp dụng cho tất cả đơn hàng.',
            ],
            [
                'code' => 'CF-004',
                'name' => '🌟 Hoa hồng VIP Account',
                'type' => 'commission',
                'calculation_type' => 'percentage',
                'fixed_amount' => null,
                'percentage' => 5.0,
                'formula' => null,
                'apply_to' => 'customer',
                'apply_conditions' => json_encode(['customer_ids' => [1, 2, 4]]),
                'is_active' => true,
                'description' => 'Hoa hồng đặc biệt 5% cho khách hàng VIP Platinum và Gold.',
            ],
            [
                'code' => 'CF-005',
                'name' => '📢 Chi phí Marketing Online',
                'type' => 'marketing',
                'calculation_type' => 'percentage',
                'fixed_amount' => null,
                'percentage' => 2.5,
                'formula' => null,
                'apply_to' => 'all',
                'apply_conditions' => null,
                'is_active' => true,
                'description' => 'Chi phí quảng cáo Google/Facebook Ads tính 2.5% trên doanh thu.',
            ],
            [
                'code' => 'CF-006',
                'name' => '🔒 Phí triển khai Fortinet',
                'type' => 'other',
                'calculation_type' => 'percentage',
                'fixed_amount' => null,
                'percentage' => 8.0,
                'formula' => null,
                'apply_to' => 'product',
                'apply_conditions' => json_encode(['product_ids' => [4, 5, 7, 8]]),
                'is_active' => true,
                'description' => 'Chi phí triển khai, cấu hình thiết bị bảo mật Fortinet (FortiGate, FortiSwitch, FortiAP).',
            ],
            [
                'code' => 'CF-007',
                'name' => '🎁 Quà tặng khách hàng',
                'type' => 'marketing',
                'calculation_type' => 'fixed',
                'fixed_amount' => 500000,
                'percentage' => null,
                'formula' => null,
                'apply_to' => 'customer',
                'apply_conditions' => json_encode(['customer_ids' => [1, 2]]),
                'is_active' => true,
                'description' => 'Chi phí quà tặng cố định cho khách hàng VIP vào dịp lễ, Tết.',
            ],
            [
                'code' => 'CF-008',
                'name' => '🛠️ Chi phí bảo hành mở rộng',
                'type' => 'other',
                'calculation_type' => 'percentage',
                'fixed_amount' => null,
                'percentage' => 5.0,
                'formula' => null,
                'apply_to' => 'product',
                'apply_conditions' => json_encode(['product_ids' => [1, 6]]),
                'is_active' => true,
                'description' => 'Chi phí bảo hành mở rộng thêm 1 năm cho Laptop và Màn hình.',
            ],
            [
                'code' => 'CF-009',
                'name' => '📊 Phí tư vấn giải pháp',
                'type' => 'other',
                'calculation_type' => 'formula',
                'fixed_amount' => null,
                'percentage' => null,
                'formula' => 'revenue * 0.02 + quantity * 100000',
                'apply_to' => 'all',
                'apply_conditions' => null,
                'is_active' => false,
                'description' => 'Phí tư vấn = 2% doanh thu + 100k/sản phẩm. (Tạm ngưng)',
            ],
            [
                'code' => 'CF-010',
                'name' => '🏆 Thưởng dự án lớn',
                'type' => 'commission',
                'calculation_type' => 'fixed',
                'fixed_amount' => 10000000,
                'percentage' => null,
                'formula' => null,
                'apply_to' => 'all',
                'apply_conditions' => null,
                'is_active' => true,
                'description' => 'Thưởng cố định 10 triệu cho dự án trên 500 triệu đồng.',
            ],
        ];
        
        foreach ($formulas as $formula) {
            $formula['created_at'] = $now;
            $formula['updated_at'] = $now;
            DB::table('cost_formulas')->insert($formula);
        }
    }
}

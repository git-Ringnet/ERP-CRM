<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate existing templates to only keep the 4 customer requested ones
        Schema::disableForeignKeyConstraints();
        DB::table('payment_template_items')->truncate();
        DB::table('payment_templates')->truncate();
        Schema::enableForeignKeyConstraints();

        $templates = [
            [
                'name' => 'Thanh toán 100% trước khi đặt hàng',
                'description' => 'Hệ thống bắt buộc Sales đính kèm UNC/chứng từ thanh toán và Finance xác nhận đã nhận tiền thì mới cho gửi yêu cầu đặt hàng cho PO Team.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Thanh toán 100%',
                        'percentage' => 100.00,
                        'trigger_type' => 'ON_CONTRACT_SIGNED',
                        'blocking_stage' => 'BLOCK_PO_SEND',
                        'due_base' => 'contract_date',
                        'due_days' => 0,
                        'required_docs' => 'unc',
                    ]
                ]
            ],
            [
                'name' => 'Thanh toán 100% sau khi giao hàng hoặc xuất hóa đơn (30 ngày)',
                'description' => 'Không cần chặn bước đặt hàng hoặc xuất hàng. Hệ thống chỉ cần tạo hạn công nợ để Sales và Finance theo dõi.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Thanh toán sau giao hàng',
                        'percentage' => 100.00,
                        'trigger_type' => 'ON_GOODS_DELIVERED',
                        'blocking_stage' => null,
                        'due_base' => 'delivery_date',
                        'due_days' => 30,
                        'required_docs' => 'none',
                    ]
                ]
            ],
            [
                'name' => 'Đặt cọc và chia nhiều đợt thanh toán (30% cọc - 70% trước xuất hàng)',
                'description' => 'Hệ thống tách từng đợt thanh toán thành từng dòng riêng, đợt 1 chặn trước đặt hàng, đợt 2 chặn trước xuất hàng.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Đặt cọc đợt 1 (Cọc 30%)',
                        'percentage' => 30.00,
                        'trigger_type' => 'ON_CONTRACT_SIGNED',
                        'blocking_stage' => 'BLOCK_PO_SEND',
                        'due_base' => 'contract_date',
                        'due_days' => 5,
                        'required_docs' => 'unc',
                    ],
                    [
                        'sort_order' => 2,
                        'milestone_name' => 'Thanh toán đợt 2 (70%)',
                        'percentage' => 70.00,
                        'trigger_type' => 'ON_GOODS_DELIVERED',
                        'blocking_stage' => 'BLOCK_WAREHOUSE_EXPORT',
                        'due_base' => 'delivery_date',
                        'due_days' => 0,
                        'required_docs' => 'unc',
                    ]
                ]
            ],
            [
                'name' => 'Trường hợp ngoại lệ được BOD phê duyệt',
                'description' => 'Nếu chưa đủ điều kiện thanh toán nhưng được BOD duyệt cho xử lý tiếp, hệ thống cho phép đi tiếp nhưng bắt buộc đính kèm file phê duyệt và đánh dấu trạng thái chưa thanh toán/ngoại lệ.',
                'items' => [
                    [
                        'sort_order' => 1,
                        'milestone_name' => 'Ngoại lệ thanh toán',
                        'percentage' => 100.00,
                        'trigger_type' => 'ON_CONTRACT_SIGNED',
                        'blocking_stage' => null,
                        'due_base' => 'contract_date',
                        'due_days' => 0,
                        'required_docs' => 'none',
                    ]
                ]
            ]
        ];

        foreach ($templates as $t) {
            // Generate uppercase snake case code from name
            $code = strtoupper(Str::slug($t['name'], '_'));

            $templateId = DB::table('payment_templates')->insertGetId([
                'code' => $code,
                'name' => $t['name'],
                'description' => $t['description'],
                'version' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            foreach ($t['items'] as $item) {
                DB::table('payment_template_items')->insert(array_merge($item, [
                    'template_id' => $templateId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]));
            }
        }
    }
}

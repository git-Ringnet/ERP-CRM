<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Thu dịch vụ', 'type' => 'income'],
            ['name' => 'Bán phế liệu', 'type' => 'income'],
            ['name' => 'Thu khác', 'type' => 'income'],
            ['name' => 'Lương nhân viên', 'type' => 'expense'],
            ['name' => 'Tiền thuê mặt bằng', 'type' => 'expense'],
            ['name' => 'Tiền điện nước', 'type' => 'expense'],
            ['name' => 'Vật tư văn phòng', 'type' => 'expense'],
            ['name' => 'Chi phí khác', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            \App\Models\TransactionCategory::updateOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                $category
            );
        }
    }
}

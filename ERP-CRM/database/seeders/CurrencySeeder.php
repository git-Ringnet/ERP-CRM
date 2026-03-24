<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Currency::updateOrCreate(
            ['code' => 'VND'],
            [
                'name' => 'Vietnamese Dong',
                'name_vi' => 'Đồng Việt Nam',
                'symbol' => '₫',
                'decimal_places' => 0,
                'is_base' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );
    }
}

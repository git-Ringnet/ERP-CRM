<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            // Current list... (Optional: I'll just append the new ones)
            ['code' => 'FN001', 'name' => 'Fortinet', 'email' => 'fortinet@example.com', 'phone' => '000000000'],
            ['code' => 'AN001', 'name' => 'Array Network', 'email' => 'array@example.com', 'phone' => '000000000'],
            ['code' => 'ZN001', 'name' => 'Zyxel Network', 'email' => 'zyxel@example.com', 'phone' => '000000000'],
            ['code' => 'QN001', 'name' => 'Qnap', 'email' => 'qnap@example.com', 'phone' => '000000000'],
            ['code' => 'BD001', 'name' => 'Bitdefender', 'email' => 'bitdefender@example.com', 'phone' => '000000000'],
            ['code' => 'SC001', 'name' => 'Secui', 'email' => 'secui@example.com', 'phone' => '000000000'],
            ['code' => 'CP001', 'name' => 'CP Plus', 'email' => 'cpplus@example.com', 'phone' => '000000000'],
            ['code' => 'GIB001', 'name' => 'Group-IB', 'email' => 'groupib@example.com', 'phone' => '000000000'],
            ['code' => 'BQ001', 'name' => 'Ben Q', 'email' => 'benq@example.com', 'phone' => '000000000'],
            ['code' => 'TP001', 'name' => 'TP-Link', 'email' => 'tplink@example.com', 'phone' => '000000000'],
            ['code' => 'SW001', 'name' => 'Sonicwall', 'email' => 'sonicwall@example.com', 'phone' => '000000000'],
            ['code' => 'PL001', 'name' => 'Perle', 'email' => 'perle@example.com', 'phone' => '000000000'],
            ['code' => 'ND001', 'name' => 'Norden', 'email' => 'norden@example.com', 'phone' => '000000000'],
            ['code' => 'OTH001', 'name' => 'Other', 'email' => 'other@example.com', 'phone' => '000000000'],
        ];

        $now = Carbon::now();
        foreach ($suppliers as $supplier) {
            DB::table('suppliers')->updateOrInsert(
                ['name' => $supplier['name']],
                array_merge($supplier, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}

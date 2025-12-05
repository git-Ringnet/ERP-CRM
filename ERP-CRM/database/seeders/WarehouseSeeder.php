<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users for managers (employees have employee_code)
        $employees = User::whereNotNull('employee_code')->pluck('id')->toArray();
        
        if (empty($employees)) {
            $employees = User::pluck('id')->toArray();
        }
        
        if (empty($employees)) {
            $employees = [null];
        }
        
        $warehouses = [
            [
                'code' => 'WH0001',
                'name' => 'Kho Chính HCM',
                'type' => 'physical',
                'address' => '123 Nguyễn Văn Linh, Quận 7, TP.HCM',
                'area' => 500.00,
                'capacity' => 10000,
                'manager_id' => $employees[0] ?? null,
                'phone' => '028-1234-5678',
                'status' => 'active',
                'product_type' => 'Điện tử, Linh kiện',
                'has_temperature_control' => true,
                'has_security_system' => true,
                'note' => 'Kho chính tại TP.HCM, hoạt động 24/7',
            ],
            [
                'code' => 'WH0002',
                'name' => 'Kho Hà Nội',
                'type' => 'physical',
                'address' => '456 Phạm Văn Đồng, Cầu Giấy, Hà Nội',
                'area' => 350.00,
                'capacity' => 7000,
                'manager_id' => $employees[1] ?? null,
                'phone' => '024-9876-5432',
                'status' => 'active',
                'product_type' => 'Điện tử, Phụ kiện',
                'has_temperature_control' => true,
                'has_security_system' => true,
                'note' => 'Kho phía Bắc',
            ],
            [
                'code' => 'WH0003',
                'name' => 'Kho Đà Nẵng',
                'type' => 'physical',
                'address' => '789 Nguyễn Văn Linh, Hải Châu, Đà Nẵng',
                'area' => 200.00,
                'capacity' => 4000,
                'manager_id' => $employees[2] ?? null,
                'phone' => '0236-123-4567',
                'status' => 'active',
                'product_type' => 'Điện tử',
                'has_temperature_control' => false,
                'has_security_system' => true,
                'note' => 'Kho miền Trung',
            ],
            [
                'code' => 'WH0004',
                'name' => 'Kho Lạnh Thực Phẩm',
                'type' => 'physical',
                'address' => '321 Quốc lộ 1A, Bình Chánh, TP.HCM',
                'area' => 150.00,
                'capacity' => 2000,
                'manager_id' => $employees[0] ?? null,
                'phone' => '028-5555-6666',
                'status' => 'active',
                'product_type' => 'Thực phẩm đông lạnh',
                'has_temperature_control' => true,
                'has_security_system' => true,
                'note' => 'Kho lạnh chuyên dụng, nhiệt độ -18°C đến 4°C',
            ],
            [
                'code' => 'WH0005',
                'name' => 'Kho Ảo Online',
                'type' => 'virtual',
                'address' => null,
                'area' => null,
                'capacity' => null,
                'manager_id' => null,
                'phone' => null,
                'status' => 'active',
                'product_type' => 'Sản phẩm số, License',
                'has_temperature_control' => false,
                'has_security_system' => false,
                'note' => 'Kho ảo cho sản phẩm số và license phần mềm',
            ],
            [
                'code' => 'WH0006',
                'name' => 'Kho Bảo Trì',
                'type' => 'physical',
                'address' => '555 Lê Văn Việt, Quận 9, TP.HCM',
                'area' => 100.00,
                'capacity' => 1500,
                'manager_id' => $employees[1] ?? null,
                'phone' => '028-7777-8888',
                'status' => 'maintenance',
                'product_type' => 'Linh kiện, Phụ tùng',
                'has_temperature_control' => false,
                'has_security_system' => true,
                'note' => 'Đang nâng cấp hệ thống kệ hàng',
            ],
            [
                'code' => 'WH0007',
                'name' => 'Kho Cũ Quận 2',
                'type' => 'physical',
                'address' => '999 Xa lộ Hà Nội, Quận 2, TP.HCM',
                'area' => 250.00,
                'capacity' => 5000,
                'manager_id' => null,
                'phone' => null,
                'status' => 'inactive',
                'product_type' => null,
                'has_temperature_control' => false,
                'has_security_system' => false,
                'note' => 'Đã ngừng hoạt động, chờ thanh lý',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }
    }
}

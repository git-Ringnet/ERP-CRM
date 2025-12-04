<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = [
            [
                'employee_code' => 'NV001',
                'name' => 'Nguyễn Văn An',
                'email' => 'an.nguyen@company.vn',
                'password' => Hash::make('password123'),
                'birth_date' => '1990-05-15',
                'phone' => '0901111111',
                'address' => '123 Lê Văn Việt, Quận 9, TP.HCM',
                'id_card' => '079090001234',
                'department' => 'Kinh doanh',
                'position' => 'Trưởng phòng',
                'join_date' => '2020-01-15',
                'salary' => 25000000,
                'bank_account' => '1234567890',
                'bank_name' => 'Vietcombank',
                'status' => 'active',
                'note' => 'Nhân viên xuất sắc năm 2023',
            ],
            [
                'employee_code' => 'NV002',
                'name' => 'Trần Thị Bình',
                'email' => 'binh.tran@company.vn',
                'password' => Hash::make('password123'),
                'birth_date' => '1992-08-20',
                'phone' => '0902222222',
                'address' => '456 Võ Văn Ngân, Thủ Đức, TP.HCM',
                'id_card' => '079092002345',
                'department' => 'Kế toán',
                'position' => 'Kế toán trưởng',
                'join_date' => '2019-03-10',
                'salary' => 22000000,
                'bank_account' => '2345678901',
                'bank_name' => 'Techcombank',
                'status' => 'active',
                'note' => null,
            ],
            [
                'employee_code' => 'NV003',
                'name' => 'Lê Văn Cường',
                'email' => 'cuong.le@company.vn',
                'password' => Hash::make('password123'),
                'birth_date' => '1995-03-12',
                'phone' => '0903333333',
                'address' => '789 Nguyễn Văn Linh, Quận 7, TP.HCM',
                'id_card' => '079095003456',
                'department' => 'Kho',
                'position' => 'Nhân viên kho',
                'join_date' => '2021-06-01',
                'salary' => 12000000,
                'bank_account' => '3456789012',
                'bank_name' => 'ACB',
                'status' => 'active',
                'note' => null,
            ],
            [
                'employee_code' => 'NV004',
                'name' => 'Phạm Thị Dung',
                'email' => 'dung.pham@company.vn',
                'password' => Hash::make('password123'),
                'birth_date' => '1988-11-25',
                'phone' => '0904444444',
                'address' => '321 Phan Văn Trị, Gò Vấp, TP.HCM',
                'id_card' => '079088004567',
                'department' => 'Nhân sự',
                'position' => 'Trưởng phòng',
                'join_date' => '2018-02-20',
                'salary' => 24000000,
                'bank_account' => '4567890123',
                'bank_name' => 'VPBank',
                'status' => 'active',
                'note' => 'Phụ trách tuyển dụng',
            ],
            [
                'employee_code' => 'NV005',
                'name' => 'Hoàng Văn Em',
                'email' => 'em.hoang@company.vn',
                'password' => Hash::make('password123'),
                'birth_date' => '1993-07-08',
                'phone' => '0905555555',
                'address' => '654 Quang Trung, Gò Vấp, TP.HCM',
                'id_card' => '079093005678',
                'department' => 'IT',
                'position' => 'Lập trình viên',
                'join_date' => '2020-09-15',
                'salary' => 18000000,
                'bank_account' => '5678901234',
                'bank_name' => 'Sacombank',
                'status' => 'active',
                'note' => null,
            ],
            [
                'employee_code' => 'NV006',
                'name' => 'Võ Thị Phương',
                'email' => 'phuong.vo@company.vn',
                'password' => Hash::make('password123'),
                'birth_date' => '1991-04-18',
                'phone' => '0906666666',
                'address' => '111 Lý Thường Kiệt, Quận 10, TP.HCM',
                'id_card' => '079091006789',
                'department' => 'Kinh doanh',
                'position' => 'Nhân viên kinh doanh',
                'join_date' => '2021-01-10',
                'salary' => 15000000,
                'bank_account' => '6789012345',
                'bank_name' => 'MB Bank',
                'status' => 'leave',
                'note' => 'Nghỉ thai sản',
            ],
        ];

        $now = Carbon::now();
        foreach ($employees as $employee) {
            $employee['created_at'] = $now;
            $employee['updated_at'] = $now;
            DB::table('users')->insert($employee);
        }
    }
}

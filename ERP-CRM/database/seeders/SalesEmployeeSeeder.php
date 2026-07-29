<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SalesEmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Retrieve admin user ID for assigned_by reference
        $admin = User::where('email', 'admin@erp.com')->first();
        $adminId = $admin ? $admin->id : 1;

        $employees = [
            [
                'stt' => 4,
                'employee_code' => 'NV004',
                'name' => 'Phan Thành Tài',
                'position' => 'Sales Director',
                'department' => 'BU1',
                'email' => 'tai.phan@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_manager',
            ],
            [
                'stt' => 5,
                'employee_code' => 'NV005',
                'name' => 'Nguyễn Thị Mẫn Tuệ',
                'position' => "Sales Director's Assistant",
                'department' => 'BU1',
                'email' => 'tue.nguyen@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 6,
                'employee_code' => 'NV006',
                'name' => 'Lê Đức Thanh Tùng',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'tung.le@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 7,
                'employee_code' => 'NV007',
                'name' => 'Nguyễn Hồ Nhựt Thảo',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'thao.nguyen@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 8,
                'employee_code' => 'NV008',
                'name' => 'Lê Tấn Phong',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'phong.le@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 9,
                'employee_code' => 'NV009',
                'name' => 'Lê Dương Hoàng Vịnh',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'vinh.le@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 10,
                'employee_code' => 'NV010',
                'name' => 'Võ Thị Lan Phương',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'phuong.vo@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 11,
                'employee_code' => 'NV011',
                'name' => 'Trần Nguyễn Quốc Đạt',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'dat.tran@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 12,
                'employee_code' => 'NV012',
                'name' => 'Nguyễn Thị Kim Thúy',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'thuy.nguyen@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 13,
                'employee_code' => 'NV013',
                'name' => 'Trịnh Thị Thu Hương',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'huong.trinh@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 14,
                'employee_code' => 'NV014',
                'name' => 'Nguyễn Thị Thanh Cúc',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'cuc.nguyen@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 15,
                'employee_code' => 'NV015',
                'name' => 'Nguyễn Quang Hưng',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'hung.nguyen@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 16,
                'employee_code' => 'NV016',
                'name' => 'Ngô Đình Duy Thắng',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'thang.ngo@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 18,
                'employee_code' => 'NV018',
                'name' => 'Nguyễn Phạm Bình Dương',
                'position' => 'Account Manager',
                'department' => 'BU1',
                'email' => 'duong.nguyen@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 19,
                'employee_code' => 'NV019',
                'name' => 'Mai Thế Chiến',
                'position' => 'Branch Manager Hanoi',
                'department' => 'BU2',
                'email' => 'chien.mai@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_manager',
            ],
            [
                'stt' => 20,
                'employee_code' => 'NV020',
                'name' => 'Đoàn Hùng Sang',
                'position' => 'Project Sales Manager HCM',
                'department' => 'BU2',
                'email' => 'sang.doan@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_manager',
            ],
            [
                'stt' => 21,
                'employee_code' => 'NV021',
                'name' => 'Ha Nguyen',
                'position' => 'Account Manager',
                'department' => 'BU2',
                'email' => 'ha.nguyen@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 22,
                'employee_code' => 'NV022',
                'name' => 'Vu Nguyen',
                'position' => 'Account Manager',
                'department' => 'BU2',
                'email' => 'vu.nguyen@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 23,
                'employee_code' => 'NV023',
                'name' => 'Anh Nguyen',
                'position' => 'Account Manager',
                'department' => 'BU3',
                'email' => 'anh.nguyen@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 24,
                'employee_code' => 'NV024',
                'name' => 'Huy Cao',
                'position' => 'Account Manager',
                'department' => 'BU3',
                'email' => 'huy.cao@techhorizonvn.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 25,
                'employee_code' => 'NV025',
                'name' => 'Phong Nguyen',
                'position' => 'Account Manager',
                'department' => 'BU3',
                'email' => 'phong.nguyen@techhorizon.com',
                'gender' => 'Male',
                'role_slug' => 'sales_staff',
            ],
            [
                'stt' => 26,
                'employee_code' => 'NV026',
                'name' => 'Huong Le',
                'position' => 'Sales Admin',
                'department' => 'BU3',
                'email' => 'Salesadmin01@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'order_management',
            ],
            [
                'stt' => 27,
                'employee_code' => 'NV027',
                'name' => 'Vy Vo',
                'position' => 'Account Manager',
                'department' => 'BU3',
                'email' => 'Vy.Vo@techhorizonvn.com',
                'gender' => 'Female',
                'role_slug' => 'sales_staff',
            ],
        ];

        foreach ($employees as $item) {
            $email = trim($item['email']);
            $roleSlug = $item['role_slug'];

            $user = User::where('employee_code', $item['employee_code'])
                ->orWhere('email', $email)
                ->orWhere('email', strtolower($email))
                ->first();

            $userData = [
                'employee_code' => $item['employee_code'],
                'name' => $item['name'],
                'email' => $email,
                'password' => Hash::make($email),
                'department' => $item['department'],
                'position' => $item['position'],
                'status' => 'active',
                'join_date' => $now,
            ];

            if ($user) {
                $user->update($userData);
            } else {
                $user = User::create($userData);
            }

            // Sync single role to guarantee clean role mapping
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $user->roles()->sync([$role->id => [
                    'assigned_by' => $adminId,
                    'assigned_at' => $now,
                ]]);
            }
        }
    }
}

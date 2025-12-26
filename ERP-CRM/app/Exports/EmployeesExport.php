<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * EmployeesExport handles exporting employee data to Excel
 * Requirements: 7.4
 */
class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $employees;

    public function __construct(Collection $employees)
    {
        $this->employees = $employees;
    }

    /**
     * Return the collection to export
     */
    public function collection()
    {
        return $this->employees;
    }

    /**
     * Define column headings
     * Requirements: 7.4 - employee code, name, position, department, email, phone, status, join date, and salary
     */
    public function headings(): array
    {
        return [
            'Mã nhân viên',
            'Tên nhân viên',
            'Chức vụ',
            'Phòng ban',
            'Email',
            'Số điện thoại',
            'Mật khẩu',
            'Trạng thái',
            'Ngày vào làm',
            'Lương',
            'Ngày sinh',
            'Địa chỉ',
            'CCCD/CMND',
            'Tài khoản ngân hàng',
            'Tên ngân hàng',
            'Ghi chú',
        ];
    }

    /**
     * Map each row to the desired format
     */
    public function map($employee): array
    {
        return [
            $employee->employee_code ?? '',
            $employee->name ?? '',
            $employee->position ?? '',
            $employee->department ?? '',
            $employee->email ?? '',
            $employee->phone ?? '',
            '', // Password - để trống khi export (không export password thật)
            $employee->status ?? '',
            $employee->join_date ?? '',
            $employee->salary ?? 0,
            $employee->birth_date ?? '',
            $employee->address ?? '',
            $employee->id_card ?? '',
            $employee->bank_account ?? '',
            $employee->bank_name ?? '',
            $employee->note ?? '',
        ];
    }
}


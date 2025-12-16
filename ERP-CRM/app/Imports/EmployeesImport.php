<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * EmployeesImport handles importing employee data from Excel
 */
class EmployeesImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $imported = 0;
    protected $updated = 0;

    /**
     * Map heading từ file Excel sang key chuẩn
     * Laravel Excel sẽ convert heading thành slug (lowercase, no accent, underscore)
     */
    protected function normalizeRow($row): array
    {
        $normalized = [];
        
        foreach ($row as $key => $value) {
            $normalized[$key] = $value;
        }

        // Map các key có thể có từ file Excel
        $keyMappings = [
            // Mã nhân viên
            'ma_nhan_vien' => ['ma_nhan_vien', 'manhanvien', 'ma_nv', 'manv'],
            // Tên nhân viên  
            'ten_nhan_vien' => ['ten_nhan_vien', 'tennhanvien', 'ten_nv', 'tennv', 'ho_ten', 'hoten'],
            // Chức vụ
            'chuc_vu' => ['chuc_vu', 'chucvu'],
            // Phòng ban
            'phong_ban' => ['phong_ban', 'phongban'],
            // Email
            'email' => ['email', 'e_mail'],
            // Số điện thoại
            'so_dien_thoai' => ['so_dien_thoai', 'sodienthoai', 'sdt', 'dien_thoai', 'dienthoai'],
            // Trạng thái
            'trang_thai' => ['trang_thai', 'trangthai'],
            // Ngày vào làm
            'ngay_vao_lam' => ['ngay_vao_lam', 'ngayvaolam'],
            // Lương
            'luong' => ['luong'],
            // Ngày sinh
            'ngay_sinh' => ['ngay_sinh', 'ngaysinh'],
            // Địa chỉ
            'dia_chi' => ['dia_chi', 'diachi'],
            // CCCD/CMND
            'cccd_cmnd' => ['cccdcmnd', 'cccd_cmnd', 'cmnd', 'cccd', 'so_cmnd', 'socmnd'],
            // Tài khoản ngân hàng
            'tai_khoan_ngan_hang' => ['tai_khoan_ngan_hang', 'taikhoannganh', 'taikhoannganang', 'tk_ngan_hang', 'stk'],
            // Tên ngân hàng
            'ten_ngan_hang' => ['ten_ngan_hang', 'tennganh', 'tennganang', 'ngan_hang', 'nganhang'],
            // Ghi chú
            'ghi_chu' => ['ghi_chu', 'ghichu'],
        ];

        $result = [];
        foreach ($keyMappings as $standardKey => $possibleKeys) {
            $result[$standardKey] = null;
            foreach ($possibleKeys as $possibleKey) {
                if (isset($normalized[$possibleKey]) && $normalized[$possibleKey] !== null && $normalized[$possibleKey] !== '') {
                    $result[$standardKey] = $normalized[$possibleKey];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Process the imported collection
     */
    public function collection(Collection $rows)
    {
        // Log để debug
        if ($rows->isNotEmpty()) {
            Log::info('Import Employee - First row keys: ' . json_encode(array_keys($rows->first()->toArray())));
            Log::info('Import Employee - First row data: ' . json_encode($rows->first()->toArray()));
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            
            // Normalize row data
            $data = $this->normalizeRow($row->toArray());
            
            // Skip empty rows
            if (empty($data['ma_nhan_vien']) && empty($data['ten_nhan_vien'])) {
                continue;
            }

            // Convert phone to string (Excel may convert to number and remove leading 0)
            if (isset($data['so_dien_thoai']) && is_numeric($data['so_dien_thoai'])) {
                $phone = (string) $data['so_dien_thoai'];
                // Add leading 0 if it looks like a Vietnamese phone number
                if (strlen($phone) == 9 && !str_starts_with($phone, '0')) {
                    $phone = '0' . $phone;
                }
                $data['so_dien_thoai'] = $phone;
            }

            // Convert other fields to string if needed
            $data['ma_nhan_vien'] = isset($data['ma_nhan_vien']) ? (string) $data['ma_nhan_vien'] : null;
            $data['ten_nhan_vien'] = isset($data['ten_nhan_vien']) ? (string) $data['ten_nhan_vien'] : null;
            $data['chuc_vu'] = isset($data['chuc_vu']) ? (string) $data['chuc_vu'] : null;
            $data['phong_ban'] = isset($data['phong_ban']) ? (string) $data['phong_ban'] : null;
            $data['cccd_cmnd'] = isset($data['cccd_cmnd']) ? (string) $data['cccd_cmnd'] : null;
            $data['tai_khoan_ngan_hang'] = isset($data['tai_khoan_ngan_hang']) ? (string) $data['tai_khoan_ngan_hang'] : null;

            // Validate row data
            $validator = Validator::make($data, [
                'ma_nhan_vien' => 'required|max:50',
                'ten_nhan_vien' => 'required|max:255',
                'email' => 'required|email|max:255',
                'so_dien_thoai' => 'required|max:20',
                'phong_ban' => 'required|max:100',
                'chuc_vu' => 'required|max:100',
            ], [
                'ma_nhan_vien.required' => "Dòng {$rowNumber}: Mã nhân viên là bắt buộc",
                'ten_nhan_vien.required' => "Dòng {$rowNumber}: Tên nhân viên là bắt buộc",
                'email.required' => "Dòng {$rowNumber}: Email là bắt buộc",
                'email.email' => "Dòng {$rowNumber}: Email không hợp lệ",
                'so_dien_thoai.required' => "Dòng {$rowNumber}: Số điện thoại là bắt buộc",
                'phong_ban.required' => "Dòng {$rowNumber}: Phòng ban là bắt buộc",
                'chuc_vu.required' => "Dòng {$rowNumber}: Chức vụ là bắt buộc",
            ]);

            if ($validator->fails()) {
                $this->errors = array_merge($this->errors, $validator->errors()->all());
                continue;
            }

            // Map status
            $status = $this->mapStatus($data['trang_thai'] ?? 'Đang làm việc');

            // Prepare insert/update data
            $employeeData = [
                'employee_code' => trim((string) ($data['ma_nhan_vien'] ?? '')),
                'name' => trim((string) ($data['ten_nhan_vien'] ?? '')),
                'position' => trim((string) ($data['chuc_vu'] ?? '')),
                'department' => trim((string) ($data['phong_ban'] ?? '')),
                'email' => trim((string) ($data['email'] ?? '')),
                'phone' => trim((string) ($data['so_dien_thoai'] ?? '')),
                'status' => $status,
                'join_date' => $this->parseDate($data['ngay_vao_lam'] ?? null),
                'salary' => $this->parseNumber($data['luong'] ?? 0),
                'birth_date' => $this->parseDate($data['ngay_sinh'] ?? null),
                'address' => trim((string) ($data['dia_chi'] ?? '')),
                'id_card' => trim((string) ($data['cccd_cmnd'] ?? '')),
                'bank_account' => trim((string) ($data['tai_khoan_ngan_hang'] ?? '')),
                'bank_name' => trim((string) ($data['ten_ngan_hang'] ?? '')),
                'note' => trim((string) ($data['ghi_chu'] ?? '')),
                'updated_at' => now(),
            ];

            // Check if employee exists
            $existing = DB::table('users')
                ->where('employee_code', $employeeData['employee_code'])
                ->first();

            if ($existing) {
                DB::table('users')
                    ->where('id', $existing->id)
                    ->update($employeeData);
                $this->updated++;
            } else {
                $employeeData['password'] = bcrypt('password123');
                $employeeData['created_at'] = now();
                DB::table('users')->insert($employeeData);
                $this->imported++;
            }
        }
    }

    /**
     * Map Vietnamese status to database value
     */
    protected function mapStatus(?string $status): string
    {
        if (empty($status)) {
            return 'active';
        }
        
        $statusLower = mb_strtolower(trim($status));
        
        $statusMap = [
            'đang làm việc' => 'active',
            'dang lam viec' => 'active',
            'active' => 'active',
            'nghỉ phép' => 'leave',
            'nghi phep' => 'leave',
            'leave' => 'leave',
            'đã nghỉ việc' => 'resigned',
            'da nghi viec' => 'resigned',
            'resigned' => 'resigned',
        ];

        return $statusMap[$statusLower] ?? 'active';
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // If it's a numeric value (Excel serial date)
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try to parse as date string
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse number from string
     */
    protected function parseNumber($value): float
    {
        if (empty($value)) {
            return 0;
        }
        
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        // Remove formatting characters
        $value = str_replace([',', ' '], ['', ''], $value);
        return (float) $value;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->imported;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }
}

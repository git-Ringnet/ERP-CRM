<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomersImport implements ToCollection, WithHeadingRow
{
    protected $errors = [];
    protected $imported = 0;
    protected $updated = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                // Skip empty rows
                if (empty(array_filter($row->toArray()))) {
                    continue;
                }

                $code = trim($row['ma_kh'] ?? $row['code'] ?? '');
                $name = trim($row['ten_khach_hang'] ?? $row['name'] ?? '');

                // Skip if no code or name
                if (empty($code) || empty($name)) {
                    $this->errors[] = "Dòng {$rowNumber}: Thiếu mã KH hoặc tên khách hàng";
                    continue;
                }

                // Validate email format
                $email = trim($row['email'] ?? '');
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = "Dòng {$rowNumber}: Email không hợp lệ '{$email}'";
                    continue;
                }

                // Parse type
                $typeRaw = trim($row['loai'] ?? $row['type'] ?? 'normal');
                $type = $this->parseType($typeRaw);

                // Parse debt_limit
                $debtLimit = $this->parseNumber($row['han_muc_no'] ?? $row['debt_limit'] ?? 0);

                // Parse debt_days
                $debtDays = (int) ($row['so_ngay_no'] ?? $row['debt_days'] ?? 0);

                $data = [
                    'code' => $code,
                    'name' => $name,
                    'email' => $email ?: null,
                    'phone' => trim($row['dien_thoai'] ?? $row['phone'] ?? '') ?: null,
                    'address' => trim($row['dia_chi'] ?? $row['address'] ?? '') ?: null,
                    'type' => $type,
                    'tax_code' => trim($row['ma_so_thue'] ?? $row['tax_code'] ?? '') ?: null,
                    'website' => trim($row['website'] ?? '') ?: null,
                    'contact_person' => trim($row['nguoi_lien_he'] ?? $row['contact_person'] ?? '') ?: null,
                    'debt_limit' => $debtLimit,
                    'debt_days' => $debtDays,
                    'note' => trim($row['ghi_chu'] ?? $row['note'] ?? '') ?: null,
                ];

                // Check if customer exists
                $existing = Customer::where('code', $code)->first();
                if ($existing) {
                    $existing->update($data);
                    $this->updated++;
                } else {
                    Customer::create($data);
                    $this->imported++;
                }
            }

            if (!empty($this->errors)) {
                DB::rollBack();
                return;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = 'Lỗi: ' . $e->getMessage();
        }
    }

    protected function parseType(string $type): string
    {
        $type = strtolower(trim($type));
        if (in_array($type, ['vip', 'v', '1'])) {
            return 'vip';
        }
        return 'normal';
    }

    protected function parseNumber($value): float
    {
        if (empty($value)) {
            return 0;
        }
        // Remove thousand separators and convert
        $value = str_replace(['.', ',', ' ', 'đ', 'd'], ['', '.', '', '', ''], $value);
        return (float) $value;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    /**
     * Generate Customer Excel template
     */
    public static function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Khách Hàng');

        $headers = [
            'Mã KH', 'Tên khách hàng', 'Email', 'Điện thoại', 'Địa chỉ',
            'Loại', 'Mã số thuế', 'Website', 'Người liên hệ',
            'Hạn mức nợ', 'Số ngày nợ', 'Ghi chú'
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->getStyle('A1:L1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:L1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            ['KH001', 'Công ty TNHH ABC', 'contact@abc.com.vn', '0901234567', '123 Nguyễn Văn Linh, Q7, TP.HCM', 'VIP', '0123456789', 'https://abc.com.vn', 'Nguyễn Văn A', 500000000, 30, 'Khách hàng lớn'],
            ['KH002', 'Công ty CP XYZ', 'info@xyz.vn', '0912345678', '456 Phạm Văn Đồng, Cầu Giấy, Hà Nội', 'VIP', '9876543210', 'https://xyz.vn', 'Trần Thị B', 300000000, 45, ''],
            ['KH003', 'Cửa hàng Minh Phát', 'minhphat@gmail.com', '0923456789', '789 Lê Lợi, Q1, TP.HCM', 'Thường', '', '', 'Lê Văn C', 50000000, 15, ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:L{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'customer_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }
}

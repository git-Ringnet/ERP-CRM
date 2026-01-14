<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuppliersImport implements ToCollection, WithHeadingRow
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

                if (empty(array_filter($row->toArray()))) {
                    continue;
                }

                $code = trim($row['ma_ncc'] ?? $row['code'] ?? '');
                $name = trim($row['ten_nha_cung_cap'] ?? $row['name'] ?? '');

                if (empty($code) || empty($name)) {
                    $this->errors[] = "Dòng {$rowNumber}: Thiếu mã NCC hoặc tên nhà cung cấp";
                    continue;
                }

                $email = trim($row['email'] ?? '');
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = "Dòng {$rowNumber}: Email không hợp lệ '{$email}'";
                    continue;
                }

                $paymentTerms = (int) ($row['thoi_han_thanh_toan'] ?? $row['payment_terms'] ?? 0);
                $baseDiscount = $this->parseNumber($row['chiet_khau_co_ban'] ?? $row['base_discount'] ?? 0);
                $volumeDiscount = $this->parseNumber($row['chiet_khau_so_luong'] ?? $row['volume_discount'] ?? 0);
                $volumeThreshold = (int) ($row['nguong_so_luong'] ?? $row['volume_threshold'] ?? 0);
                $earlyPaymentDiscount = $this->parseNumber($row['chiet_khau_thanh_toan_som'] ?? $row['early_payment_discount'] ?? 0);
                $earlyPaymentDays = (int) ($row['so_ngay_thanh_toan_som'] ?? $row['early_payment_days'] ?? 0);

                $data = [
                    'code' => $code,
                    'name' => $name,
                    'email' => $email ?: null,
                    'phone' => trim($row['dien_thoai'] ?? $row['phone'] ?? '') ?: null,
                    'address' => trim($row['dia_chi'] ?? $row['address'] ?? '') ?: null,
                    'tax_code' => trim($row['ma_so_thue'] ?? $row['tax_code'] ?? '') ?: null,
                    'website' => trim($row['website'] ?? '') ?: null,
                    'contact_person' => trim($row['nguoi_lien_he'] ?? $row['contact_person'] ?? '') ?: null,
                    'payment_terms' => $paymentTerms,
                    'product_type' => trim($row['loai_san_pham'] ?? $row['product_type'] ?? '') ?: null,
                    'base_discount' => $baseDiscount,
                    'volume_discount' => $volumeDiscount,
                    'volume_threshold' => $volumeThreshold,
                    'early_payment_discount' => $earlyPaymentDiscount,
                    'early_payment_days' => $earlyPaymentDays,
                    'note' => trim($row['ghi_chu'] ?? $row['note'] ?? '') ?: null,
                    'updated_at' => now(),
                ];

                $existing = DB::table('suppliers')->where('code', $code)->first();
                if ($existing) {
                    DB::table('suppliers')->where('id', $existing->id)->update($data);
                    $this->updated++;
                } else {
                    $data['created_at'] = now();
                    DB::table('suppliers')->insert($data);
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

    protected function parseNumber($value): float
    {
        if (empty($value)) {
            return 0;
        }
        $value = str_replace(['.', ',', ' ', 'đ', 'd', '%'], ['', '.', '', '', '', ''], $value);
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

    public static function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nhà Cung Cấp');

        $headers = [
            'ma_ncc', 'ten_nha_cung_cap', 'email', 'dien_thoai', 'dia_chi',
            'ma_so_thue', 'website', 'nguoi_lien_he', 'thoi_han_thanh_toan',
            'loai_san_pham', 'chiet_khau_co_ban', 'chiet_khau_so_luong', 'nguong_so_luong', 'ghi_chu'
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);
        $sheet->getStyle('A1:N1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:N1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            ['NCC001', 'Công ty TNHH Thiết bị ABC', 'sales@abc.com.vn', '0901234567', '123 Nguyễn Văn Linh, Q7, TP.HCM', '0123456789', 'https://abc.com.vn', 'Nguyễn Văn A', 30, 'Thiết bị điện', 5, 3, 100, 'NCC chính'],
            ['NCC002', 'Công ty CP Vật tư XYZ', 'info@xyz.vn', '0912345678', '456 Phạm Văn Đồng, Cầu Giấy, Hà Nội', '9876543210', 'https://xyz.vn', 'Trần Thị B', 45, 'Vật tư xây dựng', 3, 2, 50, ''],
            ['NCC003', 'Đại lý Minh Phát', 'minhphat@gmail.com', '0923456789', '789 Lê Lợi, Q1, TP.HCM', '', '', 'Lê Văn C', 15, 'Phụ kiện', 2, 0, 0, ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:N{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'supplier_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }
}
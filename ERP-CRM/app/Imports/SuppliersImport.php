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

                // Skip empty rows
                if (empty(array_filter($row->toArray()))) {
                    continue;
                }

                $code = trim($row['ma_ncc'] ?? $row['code'] ?? '');
                $name = trim($row['ten_nha_cung_cap'] ?? $row['name'] ?? '');

                // Skip if no code or name
                if (empty($code) || empty($name)) {
                    $this->errors[] = "Dòng {$rowNumber}: Thiếu mã NCC hoặc tên nhà cung cấp";
                    continue;
                }

                // Validate email format
                $email = trim($row['email'] ?? '');
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = "Dòng {$rowNumber}: Email không hợp lệ '{$email}'";
                    continue;
                }

                // Parse numbers
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

                // Check if supplier exists
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

    /**
     * Generate Supplier Excel template
     */
    public static function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();

        // Instructions Sheet
        $instructionsSheet = $spreadsheet->createSheet(0);
        $instructionsSheet->setTitle('Huong Dan');
        $instructionsSheet->setCellValue('A1', 'Hướng Dẫn Import Nhà Cung Cấp');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $instructions = [
            ['Cột', 'Mô tả', 'Bắt buộc', 'Định dạng'],
            ['Mã NCC', 'Mã nhà cung cấp (duy nhất)', 'Có', 'Text (VD: NCC001)'],
            ['Tên nhà cung cấp', 'Tên đầy đủ của NCC', 'Có', 'Text'],
            ['Email', 'Địa chỉ email', 'Không', 'Email hợp lệ'],
            ['Điện thoại', 'Số điện thoại', 'Không', 'Text'],
            ['Địa chỉ', 'Địa chỉ NCC', 'Không', 'Text'],
            ['Mã số thuế', 'Mã số thuế doanh nghiệp', 'Không', 'Text'],
            ['Website', 'Website của NCC', 'Không', 'URL'],
            ['Người liên hệ', 'Tên người liên hệ', 'Không', 'Text'],
            ['Thời hạn thanh toán', 'Số ngày thanh toán', 'Không', 'Số (VD: 30)'],
            ['Loại sản phẩm', 'Loại sản phẩm cung cấp', 'Không', 'Text'],
            ['Chiết khấu cơ bản', 'Chiết khấu cơ bản (%)', 'Không', 'Số (VD: 5)'],
            ['Chiết khấu số lượng', 'Chiết khấu theo số lượng (%)', 'Không', 'Số (VD: 3)'],
            ['Ngưỡng số lượng', 'Số lượng tối thiểu để được CK', 'Không', 'Số (VD: 100)'],
            ['Ghi chú', 'Ghi chú thêm', 'Không', 'Text'],
        ];

        $row = 3;
        foreach ($instructions as $instruction) {
            $instructionsSheet->fromArray($instruction, null, 'A' . $row);
            $row++;
        }

        $instructionsSheet->getStyle('A3:D3')->getFont()->setBold(true);
        $instructionsSheet->getStyle('A3:D3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('E0E0E0');

        foreach (range('A', 'D') as $col) {
            $instructionsSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Data Sheet
        $dataSheet = $spreadsheet->createSheet(1);
        $dataSheet->setTitle('Du Lieu');

        $headers = [
            'Mã NCC', 'Tên nhà cung cấp', 'Email', 'Điện thoại', 'Địa chỉ',
            'Mã số thuế', 'Website', 'Người liên hệ', 'Thời hạn thanh toán',
            'Loại sản phẩm', 'Chiết khấu cơ bản', 'Chiết khấu số lượng', 'Ngưỡng số lượng', 'Ghi chú'
        ];
        $dataSheet->fromArray($headers, null, 'A1');
        $dataSheet->getStyle('A1:N1')->getFont()->setBold(true);
        $dataSheet->getStyle('A1:N1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $dataSheet->getStyle('A1:N1')->getFont()->getColor()->setRGB('FFFFFF');

        // Example rows
        $examples = [
            ['NCC001', 'Công ty TNHH Thiết bị ABC', 'sales@abc.com.vn', '0901234567', '123 Nguyễn Văn Linh, Q7, TP.HCM', '0123456789', 'https://abc.com.vn', 'Nguyễn Văn A', 30, 'Thiết bị điện', 5, 3, 100, 'NCC chính'],
            ['NCC002', 'Công ty CP Vật tư XYZ', 'info@xyz.vn', '0912345678', '456 Phạm Văn Đồng, Cầu Giấy, Hà Nội', '9876543210', 'https://xyz.vn', 'Trần Thị B', 45, 'Vật tư xây dựng', 3, 2, 50, ''],
            ['NCC003', 'Đại lý Minh Phát', 'minhphat@gmail.com', '0923456789', '789 Lê Lợi, Q1, TP.HCM', '', '', 'Lê Văn C', 15, 'Phụ kiện', 2, 0, 0, ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $dataSheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'N') as $col) {
            $dataSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $dataSheet->getStyle("A1:N{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $spreadsheet->setActiveSheetIndex(1);

        $tempFile = tempnam(sys_get_temp_dir(), 'supplier_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }
}

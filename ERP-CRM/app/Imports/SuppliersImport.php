<?php

namespace App\Imports;

use App\Models\Supplier;
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
            // Group rows by supplier code
            $groupedRows = $rows->groupBy(function ($row) {
                return trim($row['ma_ncc'] ?? $row['code'] ?? '');
            });

            foreach ($groupedRows as $code => $supplierRows) {
                if (empty($code)) continue;

                $firstRow = $supplierRows->first();
                $name = trim($firstRow['ten_nha_cung_cap'] ?? $firstRow['name'] ?? '');

                if (empty($name)) {
                    $this->errors[] = "Thiếu tên nhà cung cấp cho Mã NCC: {$code}";
                    continue;
                }

                $email = trim($firstRow['email'] ?? '');
                if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = "Mã NCC {$code}: Email không hợp lệ '{$email}'";
                    continue;
                }

                $paymentTerms = (int) ($firstRow['thoi_han_thanh_toan'] ?? $firstRow['payment_terms'] ?? 0);
                $baseDiscount = $this->parseNumber($firstRow['chiet_khau_co_ban'] ?? $firstRow['base_discount'] ?? 0);
                $volumeDiscount = $this->parseNumber($firstRow['chiet_khau_so_luong'] ?? $firstRow['volume_discount'] ?? 0);
                $volumeThreshold = (int) ($firstRow['nguong_so_luong'] ?? $firstRow['volume_threshold'] ?? 0);
                $earlyPaymentDiscount = $this->parseNumber($firstRow['chiet_khau_thanh_toan_som'] ?? $firstRow['early_payment_discount'] ?? 0);
                $earlyPaymentDays = (int) ($firstRow['so_ngay_thanh_toan_som'] ?? $firstRow['early_payment_days'] ?? 0);

                $supplierData = [
                    'code' => $code,
                    'name' => $name,
                    'email' => $email ?: null,
                    'phone' => trim($firstRow['dien_thoai'] ?? $firstRow['phone'] ?? '') ?: null,
                    'address' => trim($firstRow['dia_chi'] ?? $firstRow['address'] ?? '') ?: null,
                    'tax_code' => trim($firstRow['ma_so_thue'] ?? $firstRow['tax_code'] ?? '') ?: null,
                    'website' => trim($firstRow['website'] ?? '') ?: null,
                    'payment_terms' => $paymentTerms,
                    'product_type' => trim($firstRow['loai_san_pham'] ?? $firstRow['product_type'] ?? '') ?: null,
                    'base_discount' => $baseDiscount,
                    'volume_discount' => $volumeDiscount,
                    'volume_threshold' => $volumeThreshold,
                    'early_payment_discount' => $earlyPaymentDiscount,
                    'early_payment_days' => $earlyPaymentDays,
                    'note' => trim($firstRow['ghi_chu'] ?? $firstRow['note'] ?? '') ?: null,
                ];

                $supplier = Supplier::where('code', $code)->first();
                if ($supplier) {
                    $supplier->update($supplierData);
                    $this->updated++;
                } else {
                    $supplier = Supplier::create($supplierData);
                    $this->imported++;
                }

                // Create or update Contacts for this supplier
                foreach ($supplierRows as $index => $row) {
                    $contactName = trim($row['ten_nguoi_lien_he'] ?? $row['contact_name'] ?? trim($row['nguoi_lien_he'] ?? $row['contact_person'] ?? ''));
                    $contactPosition = trim($row['chuc_vu'] ?? $row['contact_position'] ?? '');
                    $contactPhone = trim($row['sdt_nguoi_lien_he'] ?? $row['contact_phone'] ?? '');
                    $contactEmail = trim($row['email_nguoi_lien_he'] ?? $row['contact_email'] ?? '');
                    $contactNote = trim($row['ghi_chu_nguoi_lien_he'] ?? $row['contact_note'] ?? '');

                    // Only create/update if there's contact info
                    if (!empty($contactName)) {
                        $contactData = [
                            'supplier_id' => $supplier->id,
                            'name' => $contactName,
                            'position' => $contactPosition,
                            'phone' => $contactPhone,
                            'email' => $contactEmail,
                            'note' => $contactNote,
                        ];

                        // Set the first contact as primary if no primary exists
                        if ($index === 0 && !$supplier->contacts()->where('is_primary', true)->exists()) {
                            $contactData['is_primary'] = true;
                        }

                        $contact = $supplier->contacts()->where('name', $contactName)->first();
                        if ($contact) {
                            $contact->update($contactData);
                        } else {
                            $supplier->contacts()->create($contactData);
                        }
                    }
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
            'ma_so_thue', 'website', 'thoi_han_thanh_toan',
            'loai_san_pham', 'chiet_khau_co_ban', 'chiet_khau_so_luong', 'nguong_so_luong', 'ghi_chu',
            'ten_nguoi_lien_he', 'chuc_vu', 'sdt_nguoi_lien_he', 'email_nguoi_lien_he'
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
        $sheet->getStyle('A1:Q1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:Q1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            ['NCC001', 'Công ty TNHH Thiết bị ABC', 'sales@abc.com.vn', '0901234567', '123 Nguyễn Văn Linh, Q7, TP.HCM', '0123456789', 'https://abc.com.vn', 30, 'Thiết bị điện', 5, 3, 100, 'NCC chính', 'Nguyễn Văn A', 'Giám đốc', '0901112233', 'a.nguyen@abc.com.vn'],
            ['NCC001', 'Công ty TNHH Thiết bị ABC', 'sales@abc.com.vn', '0901234567', '123 Nguyễn Văn Linh, Q7, TP.HCM', '0123456789', 'https://abc.com.vn', 30, 'Thiết bị điện', 5, 3, 100, 'NCC chính', 'Trần Thị B', 'Kế toán', '0904445566', 'b.tran@abc.com.vn'],
            ['NCC002', 'Công ty CP Vật tư XYZ', 'info@xyz.vn', '0912345678', '456 Phạm Văn Đồng, Cầu Giấy, Hà Nội', '9876543210', 'https://xyz.vn', 45, 'Vật tư xây dựng', 3, 2, 50, '', 'Lê Văn C', 'Sale Manager', '0919998877', 'c.le@xyz.vn'],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:Q{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'supplier_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }
}
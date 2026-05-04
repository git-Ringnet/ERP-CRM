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
            // Group rows by tax_code
            $groupedRows = $rows->groupBy(function ($row) {
                return trim($row['ma_so_thue'] ?? $row['tax_code'] ?? '');
            });

            foreach ($groupedRows as $taxCode => $partnerRows) {
                if (empty($taxCode)) continue;

                $firstRow = $partnerRows->first();
                $partnerName = trim($firstRow['ten_doi_tac'] ?? $firstRow['partner_name'] ?? '');
                $abvName = trim($firstRow['ten_viet_tat'] ?? $firstRow['abv_name'] ?? '');
                
                // Collect all unique non-empty AMs from all rows of this partner
                $ams = $partnerRows->map(function($row) {
                    return trim($row['am'] ?? '');
                })->filter()->unique()->join(', ');

                if (empty($partnerName)) {
                    $this->errors[] = "Thiếu tên đối tác cho MST: {$taxCode}";
                    continue;
                }

                // 1. Find or create Customer (Partner)
                $customerData = [
                    'name' => $partnerName,
                    'tax_code' => $taxCode,
                    'abv_name' => $abvName,
                    'am' => $ams ?: null,
                ];

                // For new customers, we can use the first row's PIC info as fallback for company email/phone
                // This will be overridden if user enters specific company info later
                $firstPicEmail = trim($firstRow['email_pic'] ?? $firstRow['pic_email'] ?? '');
                $firstPicPhone = trim($firstRow['so_dien_thoai_pic'] ?? $firstRow['pic_phone'] ?? '');
                
                $customer = Customer::where('tax_code', $taxCode)->first();
                if (!$customer) {
                    $customerData['email'] = $firstPicEmail;
                    $customerData['phone'] = $firstPicPhone;
                    $customer = Customer::create($customerData);
                    $this->imported++;
                } else {
                    $customer->update($customerData);
                    $this->updated++;
                }

                // 2. Create or update Contacts (PICs) for this partner
                foreach ($partnerRows as $index => $row) {
                    $rowNumber = $rows->search($row) + 2;

                    $firstName = trim($row['ten'] ?? $row['first_name'] ?? '');
                    $lastName = trim($row['ho'] ?? $row['last_name'] ?? '');
                    $title = trim($row['danh_xung'] ?? $row['mr_ms_mrs'] ?? $row['mrmsmrs'] ?? '');
                    $position = trim($row['chuc_vu_pic'] ?? $row['pic_job_title'] ?? '');
                    $phone = trim($row['so_dien_thoai_pic'] ?? $row['pic_phone'] ?? '');
                    $email = trim($row['email_pic'] ?? $row['pic_email'] ?? '');

                    if (empty($firstName) || empty($position) || empty($phone) || empty($email)) {
                        $this->errors[] = "Dòng {$rowNumber}: Thiếu thông tin PIC bắt buộc (Tên, Chức vụ, SĐT, Email)";
                        continue;
                    }

                    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[] = "Dòng {$rowNumber}: Email PIC không hợp lệ '{$email}'";
                        continue;
                    }

                    $contactData = [
                        'customer_id' => $customer->id,
                        'name' => trim($firstName . ' ' . $lastName),
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'title' => $title,
                        'position' => $position,
                        'phone' => $phone,
                        'email' => $email,
                    ];

                    // Update contact by email within the same customer
                    $contact = $customer->contacts()->where('email', $email)->first();
                    if ($contact) {
                        $contact->update($contactData);
                    } else {
                        $customer->contacts()->create($contactData);
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
            'Partner Name (*)', 'Tax code (*)', 'Abv Name (*)', 'First Name (*)', 'Last Name', 
            'Mr/Ms/Mrs', 'PIC Job Title (*)', 'PIC Phone (*)', 'PIC Email (*)', 'AM'
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        $sheet->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            [
                'CÔNG TY CỔ PHẦN ĐẦU TƯ VÀ PHÁT TRIỂN CÔNG NGHỆ QUỐC GIA ADG', '0102023052', 'ADG', 'Lâm', 'Võ Tùng', 
                'Mr', 'Director Danang Branch', '(+84) 973 777 733', 'lam.vo@adg.vn', 'Vịnh'
            ],
            [
                'CÔNG TY CỔ PHẦN ĐẦU TƯ VÀ PHÁT TRIỂN CÔNG NGHỆ QUỐC GIA ADG', '0102023052', 'ADG', 'Phương', 'Trần Thị Trúc', 
                'Ms', 'Purchare & Receptionist Danang Branch', '(+84) 988 049 950', 'trucphuong.tran@adg.vn', 'Vịnh'
            ],
            [
                'CÔNG TY TNHH GIẢI PHÁP TOÀN CẦU IIJ VIỆT NAM', '0107620905', 'IIJ', 'Thiên', 'Nguyễn', 
                'Mr', 'Presales', '(+84) 919 35 39 35', 'thien.nguyen@ap.iij.com', 'Tài/ Tuệ'
            ],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'customer_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }
}

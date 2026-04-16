<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithCalculatedFormulas
{
    protected $errors = [];
    protected $warnings = [];
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

                $code = strtoupper(trim($row['ma_sp'] ?? $row['code'] ?? ''));
                $name = trim($row['ten_san_pham'] ?? $row['name'] ?? '');

                // Allow up to 1000 chars for name, truncate beyond that
                if (mb_strlen($name) > 1000) {
                    $name = mb_substr($name, 0, 1000);
                }

                if (empty($code) || empty($name)) {
                    $this->errors[] = "Dòng {$rowNumber}: Thiếu mã SP hoặc tên sản phẩm";
                    continue;
                }

                $category = strtoupper(trim($row['danh_muc'] ?? $row['category'] ?? ''));
                if (!empty($category) && !preg_match('/^[A-Z]$/', $category)) {
                    $this->warnings[] = "Dòng {$rowNumber}: Danh mục '{$category}' không hợp lệ (phải là 1 chữ cái A-Z), đã bỏ qua";
                    $category = '';
                }

                $warrantyMonths = (int) ($row['bao_hanh_thang'] ?? $row['warranty_months'] ?? 0);

                $data = [
                    'code' => $code,
                    'name' => $name,
                    'category' => $category ?: null,
                    'unit' => $this->sanitizeUnit($row['don_vi'] ?? $row['unit'] ?? 'Cái'),
                    'warranty_months' => $warrantyMonths,
                    'description' => trim($row['mo_ta'] ?? $row['description'] ?? '') ?: null,
                    'note' => trim($row['ghi_chu'] ?? $row['note'] ?? '') ?: null,
                    'updated_at' => now(),
                ];

                // Use the normalized code to find existing products
                $existing = Product::where('code', $code)->first();
                if ($existing) {
                    $existing->update($data);
                    $this->updated++;
                } else {
                    $data['created_at'] = now();
                    Product::create($data);
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

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    protected function sanitizeUnit($unit): string
    {
        $unit = trim((string)$unit);
        // If it looks like a formula (starts with =), just default to 'Cái'
        // or if it's too long (formulas can be long)
        if (str_starts_with($unit, '=') || strlen($unit) > 20) {
            return 'Cái';
        }
        return $unit ?: 'Cái';
    }

    public static function generateTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sản Phẩm');

        $headers = ['ma_sp', 'ten_san_pham', 'danh_muc', 'don_vi', 'bao_hanh_thang', 'mo_ta', 'ghi_chu'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:G1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            ['SP001', 'Máy tính xách tay Dell Latitude 5520', 'A', 'Cái', 24, 'Laptop văn phòng cao cấp', ''],
            ['SP002', 'Màn hình Dell 24 inch P2422H', 'B', 'Cái', 36, 'Màn hình IPS Full HD', ''],
            ['SP003', 'Bàn phím cơ Logitech G Pro', 'C', 'Cái', 12, 'Bàn phím gaming', ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:G{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'product_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }
}

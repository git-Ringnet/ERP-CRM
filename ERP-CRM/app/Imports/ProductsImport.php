<?php

namespace App\Imports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithCalculatedFormulas
{
    protected ?string $batchCode = null;
    protected int $currentRow = 1; // Row 1 is header
    protected array $seenCodesInFile = [];
    protected array $errors = [];
    protected array $detailedErrors = [];
    protected array $warnings = [];
    protected int $imported = 0;
    protected int $updated = 0;

    public function __construct(?string $batchCode = null)
    {
        $this->batchCode = $batchCode;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->currentRow++;
            $rowNumber = $this->currentRow;

            // Check if row is completely blank
            $rowArray = $row->toArray();
            if (empty(array_filter($rowArray, fn($val) => $val !== null && trim((string)$val) !== ''))) {
                continue;
            }

            $codeRaw = $row['ma_sp'] ?? $row['code'] ?? null;
            $nameRaw = $row['ten_san_pham'] ?? $row['name'] ?? null;

            $code = !empty($codeRaw) ? strtoupper(trim((string)$codeRaw)) : '';
            $name = !empty($nameRaw) ? trim((string)$nameRaw) : '';

            // Check 1: Missing code
            if ($code === '') {
                $this->addError($rowNumber, '(Trống)', 'Thiếu mã sản phẩm (cột ma_sp)');
                continue;
            }

            // Check 2: Code length
            if (mb_strlen($code) > 50) {
                $this->addError($rowNumber, $code, 'Mã sản phẩm vượt quá độ dài cho phép (tối đa 50 ký tự)');
                continue;
            }

            // Check 3: Duplicate code in the same Excel file
            if (isset($this->seenCodesInFile[$code])) {
                $firstRow = $this->seenCodesInFile[$code];
                $this->addError($rowNumber, $code, "Trùng lặp mã sản phẩm trong file (đã xuất hiện ở dòng {$firstRow})");
                continue;
            }
            $this->seenCodesInFile[$code] = $rowNumber;

            // Check 4: Missing name
            if ($name === '') {
                $this->addError($rowNumber, $code, 'Thiếu tên sản phẩm (cột ten_san_pham)');
                continue;
            }

            // Name length
            if (mb_strlen($name) > 1000) {
                $name = mb_substr($name, 0, 1000);
            }

            // Category check
            $category = strtoupper(trim((string)($row['danh_muc'] ?? $row['category'] ?? '')));
            if (!empty($category) && !preg_match('/^[A-Z]$/', $category)) {
                $this->warnings[] = "Dòng {$rowNumber}: Danh mục '{$category}' không hợp lệ (phải là 1 chữ cái A-Z), đã bỏ qua";
                $category = '';
            }

            // Warranty check
            $warrantyRaw = $row['bao_hanh_thang'] ?? $row['warranty_months'] ?? null;
            $warrantyMonths = 0;
            if ($warrantyRaw !== null && trim((string)$warrantyRaw) !== '') {
                if (!is_numeric($warrantyRaw) || (int)$warrantyRaw < 0) {
                    $this->addError($rowNumber, $code, "Thời gian bảo hành không hợp lệ (phải là số nguyên không âm): '{$warrantyRaw}'");
                    continue;
                }
                $warrantyMonths = (int)$warrantyRaw;
            }

            // Brand
            $brand = trim((string)($row['hang'] ?? $row['hãng'] ?? $row['brand'] ?? $row['hang_san_xuat'] ?? $row['vendor'] ?? $row['nha_san_xuat'] ?? ''));

            // Min Price
            $minPriceRaw = $row['gia_min'] ?? $row['gia_san'] ?? $row['min_price'] ?? $row['muc_gia_min'] ?? null;
            $minPrice = null;
            if ($minPriceRaw !== null && trim((string)$minPriceRaw) !== '') {
                $cleanedMin = str_replace([',', ' ', '$', 'VND', 'USD', 'vnd', 'usd'], '', (string)$minPriceRaw);
                if (!is_numeric($cleanedMin) || (float)$cleanedMin < 0) {
                    $this->addError($rowNumber, $code, "Giá Min/Giá sàn không hợp lệ: '{$minPriceRaw}'");
                    continue;
                }
                $minPrice = (float)$cleanedMin;
            }

            $minCurrency = strtoupper(trim((string)($row['tien_te_min'] ?? $row['min_price_currency'] ?? 'VND')));
            if (!in_array($minCurrency, ['VND', 'USD'], true)) {
                $minCurrency = 'VND';
            }

            // If we already have errors recorded in previous rows, skip DB operations
            if (!empty($this->errors)) {
                continue;
            }

            $data = [
                'code' => $code,
                'name' => $name,
                'category' => $category ?: null,
                'unit' => $this->sanitizeUnit($row['don_vi'] ?? $row['unit'] ?? 'Cái'),
                'warranty_months' => $warrantyMonths,
                'min_price' => $minPrice,
                'min_price_currency' => $minCurrency,
                'description' => trim((string)($row['mo_ta'] ?? $row['description'] ?? '')) ?: null,
                'note' => trim((string)($row['ghi_chu'] ?? $row['note'] ?? '')) ?: null,
                'updated_at' => now(),
            ];

            // Use the normalized code to find existing products
            $existing = Product::where('code', $code)->first();
            if ($existing) {
                if ($brand !== '') {
                    $data['brand'] = $brand;
                }
                // Do NOT overwrite import_batch for existing products to preserve prior data
                $existing->update($data);
                $this->updated++;
            } else {
                $data['brand'] = $brand ?: null;
                $data['import_batch'] = $this->batchCode;
                $data['created_at'] = now();
                Product::create($data);
                $this->imported++;
            }
        }
    }

    protected function addError(int $row, string $code, string $reason): void
    {
        $this->detailedErrors[] = [
            'row' => $row,
            'code' => $code,
            'reason' => $reason,
        ];
        $this->errors[] = "Dòng {$row} [{$code}]: {$reason}";
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getDetailedErrors(): array
    {
        return $this->detailedErrors;
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

        $headers = ['ma_sp', 'ten_san_pham', 'hang', 'don_vi', 'bao_hanh_thang', 'gia_min', 'tien_te_min', 'mo_ta', 'ghi_chu'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A1:I1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:I1')->getFont()->getColor()->setRGB('FFFFFF');

        $examples = [
            ['SP001', 'Máy tính xách tay Dell Latitude 5520', 'Dell', 'Cái', 24, 25000000, 'VND', 'Laptop văn phòng cao cấp', ''],
            ['SP002', 'Màn hình Dell 24 inch P2422H', 'Dell', 'Cái', 36, 4500000, 'VND', 'Màn hình IPS Full HD', ''],
            ['SP003', 'Bàn phím cơ Logitech G Pro', 'Logitech', 'Cái', 12, 1800000, 'VND', 'Bàn phím gaming', ''],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->fromArray($example, null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $lastRow = $row - 1;
        $sheet->getStyle("A1:I{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $tempFile = tempnam(sys_get_temp_dir(), 'product_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return $tempFile;
    }
}

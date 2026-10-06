<?php

namespace App\Imports;

use App\Models\MarketingSupplierFund;
use App\Models\MarketingSupplierTransaction;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class MarketingSupplierFundsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithCalculatedFormulas
{
    protected array $errors = [];
    protected array $warnings = [];
    protected int $imported = 0;
    protected int $updated = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id() ?: 1;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row->toArray()))) {
                    continue;
                }

                // 1. Tên Hãng / Nhà cung cấp
                $brandName = trim((string)(
                    $row['ten_hang_nha_cung_cap'] ?? 
                    $row['ten_hang'] ?? 
                    $row['hang'] ?? 
                    $row['supplier'] ?? 
                    $row['nha_cung_cap'] ?? 
                    $row['vendor'] ?? ''
                ));

                if (empty($brandName)) {
                    $this->errors[] = "Dòng {$rowNumber}: Thiếu tên Hãng / Nhà cung cấp.";
                    continue;
                }

                // Tìm hoặc tạo Supplier
                $supplier = Supplier::where('name', $brandName)
                    ->orWhere('code', $brandName)
                    ->first();

                if (!$supplier) {
                    $supplier = Supplier::where('name', 'like', "%{$brandName}%")->first();
                }

                if (!$supplier) {
                    $count = Supplier::count() + 1;
                    $newCode = 'SUP' . str_pad($count, 4, '0', STR_PAD_LEFT);
                    while (Supplier::where('code', $newCode)->exists()) {
                        $count++;
                        $newCode = 'SUP' . str_pad($count, 4, '0', STR_PAD_LEFT);
                    }
                    $supplier = Supplier::create([
                        'code' => $newCode,
                        'name' => $brandName,
                    ]);
                }

                // 2. Quý & Năm
                $rawQuarter = strtoupper(trim((string)($row['quy'] ?? $row['quarter'] ?? '')));
                $quarter = match ($rawQuarter) {
                    'Q1', '1', 'QUÝ 1', 'QUY 1' => 'Q1',
                    'Q2', '2', 'QUÝ 2', 'QUY 2' => 'Q2',
                    'Q3', '3', 'QUÝ 3', 'QUY 3' => 'Q3',
                    'Q4', '4', 'QUÝ 4', 'QUY 4' => 'Q4',
                    default => 'Q' . ceil(date('n') / 3),
                };

                $year = (int)($row['nam'] ?? $row['year'] ?? date('Y'));
                if ($year < 2020 || $year > 2100) {
                    $year = (int)date('Y');
                }

                // 3. Tên Quỹ
                $fundName = trim((string)(
                    $row['ten_quy_ho_tro'] ?? 
                    $row['ten_quy'] ?? 
                    $row['ten_chuong_trinh'] ?? 
                    $row['name'] ?? ''
                ));

                if (empty($fundName)) {
                    $fundName = "Quỹ Hãng {$supplier->name} MDF {$quarter}-{$year}";
                }

                // 4. Số tiền quỹ
                $rawAmount = (string)(
                    $row['tong_tien_quy_cap_vnd'] ?? 
                    $row['tong_tien_quy_cap'] ?? 
                    $row['tong_tien_quy'] ?? 
                    $row['so_tien'] ?? 
                    $row['amount'] ?? 0
                );
                $amount = (float) preg_replace('/[^\d.]/', '', str_replace(',', '', $rawAmount));

                if ($amount <= 0) {
                    $this->errors[] = "Dòng {$rowNumber}: Số tiền quỹ phải lớn hơn 0 ({$fundName}).";
                    continue;
                }

                // 5. Ghi chú
                $note = trim((string)(
                    $row['ghi_chu_dieu_kien'] ?? 
                    $row['ghi_chu'] ?? 
                    $row['note'] ?? ''
                ));

                // 6. Kiểm tra xem quỹ đã tồn tại chưa (theo Hãng và Tên quỹ)
                $existingFund = MarketingSupplierFund::where('supplier_id', $supplier->id)
                    ->where(function ($q) use ($fundName, $quarter, $year) {
                        $q->where('name', $fundName)
                          ->orWhere(function ($q2) use ($quarter, $year) {
                              $q2->where('quarter', $quarter)->where('year', $year);
                          });
                    })
                    ->first();

                if ($existingFund) {
                    $oldAmount = (float)$existingFund->amount;
                    $diff = $amount - $oldAmount;

                    $existingFund->update([
                        'name'             => $fundName,
                        'quarter'          => $quarter,
                        'year'             => $year,
                        'amount'           => $amount,
                        'remaining_amount' => $amount - (float)$existingFund->used_amount,
                        'note'             => $note ?: $existingFund->note,
                    ]);

                    if ($diff != 0) {
                        $type = $diff > 0 ? 'incoming' : 'adjustment';
                        $sign = $diff > 0 ? '+' : '-';
                        MarketingSupplierTransaction::create([
                            'supplier_id'                => $supplier->id,
                            'marketing_supplier_fund_id' => $existingFund->id,
                            'type'                       => $type,
                            'amount'                     => abs($diff),
                            'note'                       => "Cập nhật ngân sách quỹ từ file Excel ({$sign}" . number_format(abs($diff)) . " đ)",
                            'created_by'                 => $userId,
                        ]);
                    }

                    $this->updated++;
                } else {
                    $newFund = MarketingSupplierFund::create([
                        'supplier_id'      => $supplier->id,
                        'name'             => $fundName,
                        'quarter'          => $quarter,
                        'year'             => $year,
                        'amount'           => $amount,
                        'used_amount'      => 0,
                        'remaining_amount' => $amount,
                        'note'             => $note ?: null,
                        'created_by'       => $userId,
                    ]);

                    MarketingSupplierTransaction::create([
                        'supplier_id'                => $supplier->id,
                        'marketing_supplier_fund_id' => $newFund->id,
                        'type'                       => 'incoming',
                        'amount'                     => $amount,
                        'note'                       => "Khởi tạo quỹ hãng từ file Excel: " . $newFund->name,
                        'created_by'                 => $userId,
                    ]);

                    $this->imported++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = "Lỗi xử lý file Excel: " . $e->getMessage();
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
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

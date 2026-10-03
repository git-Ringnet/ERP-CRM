<?php

namespace App\Imports;

use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class MarketingItemsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithCalculatedFormulas
{
    protected array $errors = [];
    protected array $warnings = [];
    protected int $imported = 0;
    protected int $updated = 0;

    public function collection(Collection $rows)
    {
        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                if (empty(array_filter($row->toArray()))) {
                    continue;
                }

                $name = trim($row['ten_vat_pham'] ?? $row['ten_qua_tang'] ?? $row['name'] ?? $row['ten'] ?? '');
                if (empty($name)) {
                    $this->errors[] = "Dòng {$rowNumber}: Thiếu tên vật phẩm / quà tặng";
                    continue;
                }

                $rawCode = strtoupper(trim($row['ma_vat_pham'] ?? $row['ma_qua_tang'] ?? $row['code'] ?? $row['ma'] ?? ''));
                $code = $rawCode ?: MarketingItem::generateCode();

                $rawCategory = strtolower(trim((string)($row['danh_muc'] ?? $row['category'] ?? 'gift')));
                $category = match ($rawCategory) {
                    'gift', 'qua tang', 'quà tặng', 'qua_tang' => 'gift',
                    'publication', 'an pham', 'ấn phẩm', 'catalogue', 'brochure' => 'publication',
                    'equipment', 'thiet bi', 'thiết bị', 'standee', 'vat tu', 'vật tư' => 'equipment',
                    'clothing', 'dong phuc', 'đồng phục', 'ao thun', 'áo thun' => 'clothing',
                    default => 'gift',
                };

                $unit = trim((string)($row['don_vi'] ?? $row['don_vi_tinh'] ?? $row['unit'] ?? 'Cái'));
                $quantity = max(0, (int)($row['so_luong'] ?? $row['so_luong_nhap'] ?? $row['quantity'] ?? $row['stock'] ?? 0));
                $minAlert = max(0, (int)($row['ton_toi_thieu'] ?? $row['min_stock_alert'] ?? 10));
                
                $rawCost = (string)($row['don_gia'] ?? $row['unit_cost'] ?? $row['gia'] ?? 0);
                $unitCost = (float) preg_replace('/[^\d.]/', '', str_replace(',', '', $rawCost));
                $description = trim((string)($row['mo_ta'] ?? $row['description'] ?? $row['ghi_chu'] ?? ''));

                $existing = MarketingItem::where('code', $code)->first();

                if ($existing) {
                    $newStock = $existing->stock_quantity + $quantity;
                    $updatePayload = [
                        'name' => $name,
                        'category' => $category,
                        'unit' => $unit ?: $existing->unit,
                        'stock_quantity' => $newStock,
                        'min_stock_alert' => $minAlert,
                    ];
                    if ($unitCost > 0) {
                        $updatePayload['unit_cost'] = $unitCost;
                    }
                    if ($description) {
                        $updatePayload['description'] = $description;
                    }
                    $existing->update($updatePayload);

                    if ($quantity > 0) {
                        MarketingItemTransaction::create([
                            'marketing_item_id' => $existing->id,
                            'type'              => 'import',
                            'quantity'          => $quantity,
                            'remaining_stock'   => $newStock,
                            'created_by'        => Auth::id(),
                            'reference_code'    => 'EXCEL-IMP-' . date('YmdHis'),
                            'note'              => 'Import bổ sung số lượng từ file Excel',
                        ]);
                    }

                    $this->updated++;
                } else {
                    $item = MarketingItem::create([
                        'code'            => $code,
                        'name'            => $name,
                        'category'        => $category,
                        'unit'            => $unit ?: 'Cái',
                        'stock_quantity'  => $quantity,
                        'min_stock_alert' => $minAlert,
                        'unit_cost'       => $unitCost,
                        'description'     => $description,
                        'status'          => 'active',
                        'approval_status' => 'approved',
                        'submitted_by'    => Auth::id(),
                        'approved_by'     => Auth::id(),
                        'approved_at'     => now(),
                    ]);

                    if ($quantity > 0) {
                        MarketingItemTransaction::create([
                            'marketing_item_id' => $item->id,
                            'type'              => 'import',
                            'quantity'          => $quantity,
                            'remaining_stock'   => $quantity,
                            'created_by'        => Auth::id(),
                            'reference_code'    => 'EXCEL-NEW-' . date('YmdHis'),
                            'note'              => 'Khởi tạo tồn kho ban đầu từ file Excel',
                        ]);
                    }

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

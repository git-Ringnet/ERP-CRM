<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Import;
use App\Models\Export;
use App\Models\PurchaseOrder;
use App\Models\Sale;

class SyncVoucherCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voucher:sync-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật mã phiếu nhập kho và xuất kho theo mã PO và SO tương ứng';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->info('🔄 BẮT ĐẦU CẬP NHẬT MÃ PHIẾU NHẬP KHO THEO MÃ PO...');
        $this->info('================================================================');

        $imports = Import::where('reference_type', 'purchase_order')
            ->whereNotNull('reference_id')
            ->get();
            
        $importUpdated = 0;
        $importSkipped = 0;
        
        foreach ($imports as $import) {
            $po = PurchaseOrder::find($import->reference_id);
            if (!$po) {
                $this->warn("⚠ Không tìm thấy PO ID {$import->reference_id} cho phiếu nhập kho ID {$import->id}.");
                continue;
            }
            
            $targetCode = $po->code;
            if ($import->code === $targetCode) {
                $importSkipped++;
                continue;
            }
            
            // Tránh lỗi trùng lặp do unique constraint
            $finalCode = $targetCode;
            $counter = 1;
            while (Import::where('code', $finalCode)->where('id', '!=', $import->id)->exists()) {
                $finalCode = $targetCode . '-' . $counter;
                $counter++;
            }
            
            $oldCode = $import->code;
            $import->update(['code' => $finalCode]);
            $this->line("  - Đã cập nhật phiếu nhập kho ID {$import->id}: '{$oldCode}' -> '{$finalCode}'");
            $importUpdated++;
        }
        
        $this->info("✅ Đã hoàn thành phiếu nhập kho: Cập nhật {$importUpdated}, bỏ qua {$importSkipped}.");
        $this->newLine();

        $this->info('================================================================');
        $this->info('🔄 BẮT ĐẦU CẬP NHẬT MÃ PHIẾU XUẤT KHO THEO MÃ SO...');
        $this->info('================================================================');
        
        $exports = Export::where('reference_type', 'sale')
            ->whereNotNull('reference_id')
            ->get();
            
        $exportUpdated = 0;
        $exportSkipped = 0;
        
        foreach ($exports as $export) {
            $sale = Sale::find($export->reference_id);
            if (!$sale) {
                $this->warn("⚠ Không tìm thấy SO ID {$export->reference_id} cho phiếu xuất kho ID {$export->id}.");
                continue;
            }
            
            $targetCode = $sale->code;
            if ($export->code === $targetCode) {
                $exportSkipped++;
                continue;
            }
            
            // Tránh lỗi trùng lặp do unique constraint
            $finalCode = $targetCode;
            $counter = 1;
            while (Export::where('code', $finalCode)->where('id', '!=', $export->id)->exists()) {
                $finalCode = $targetCode . '-' . $counter;
                $counter++;
            }
            
            $oldCode = $export->code;
            $export->update(['code' => $finalCode]);
            $this->line("  - Đã cập nhật phiếu xuất kho ID {$export->id}: '{$oldCode}' -> '{$finalCode}'");
            $exportUpdated++;
        }
        
        $this->info("✅ Đã hoàn thành phiếu xuất kho: Cập nhật {$exportUpdated}, bỏ qua {$exportSkipped}.");
        $this->newLine();
        
        return self::SUCCESS;
    }
}

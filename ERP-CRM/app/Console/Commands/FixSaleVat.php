<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixSaleVat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:fix-vat
                            {--id=* : IDs of the sales orders to fix}
                            {--code=* : Codes of the sales orders to fix}
                            {--all : Fix all sales orders}
                            {--converted : Only fix sales orders converted from quotations}
                            {--default-vat= : Default VAT rate to use if no VAT is set and no source quotation is found (e.g. 8 or 10)}
                            {--dry-run : Run in preview mode without saving changes to the database}
                            {--force : Skip safety confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật và tính toán lại VAT cho các đơn hàng bán (đặc biệt là các đơn chuyển từ báo giá bị lỗi VAT 0%)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->info('================================================================');
        $this->warn('           CÔNG CỤ CẬP NHẬT & SỬA LỖI VAT ĐƠN HÀNG BÁN');
        $this->info('================================================================');

        $ids = $this->option('id');
        $codes = $this->option('code');
        $all = $this->option('all');
        $converted = $this->option('converted');
        $defaultVatInput = $this->option('default-vat');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $defaultVat = null;
        if ($defaultVatInput !== null) {
            $defaultVat = (float) $defaultVatInput;
        }

        // Interactive mode if no target filters are specified
        if (empty($ids) && empty($codes) && !$all && !$converted) {
            $input = $this->readConsoleInput('Nhập mã đơn (code), ID, "converted" (chỉ đơn từ báo giá) hoặc "all" (tất cả): ');
            $input = trim($input);

            if (empty($input)) {
                $this->error('❌ Vui lòng cung cấp mã đơn hàng, ID hoặc chọn option --all / --converted.');
                return self::FAILURE;
            }

            if (strtolower($input) === 'all') {
                $all = true;
            } elseif (strtolower($input) === 'converted') {
                $converted = true;
            } elseif (is_numeric($input)) {
                $ids = [$input];
            } else {
                $codes = [$input];
            }
        }

        // Fetch target sales
        $query = Sale::query()->with(['items', 'currency']);

        if (!$all) {
            if ($converted) {
                // Fetch sales that have a linked quotation
                $saleIdsFromQuotations = Quotation::whereNotNull('converted_to_sale_id')
                    ->pluck('converted_to_sale_id')
                    ->filter()
                    ->unique()
                    ->toArray();
                
                $query->whereIn('id', $saleIdsFromQuotations);
            } else {
                $query->where(function($q) use ($ids, $codes) {
                    if (!empty($ids)) {
                        $q->orWhereIn('id', $ids);
                    }
                    if (!empty($codes)) {
                        $q->orWhereIn('code', $codes);
                    }
                });
            }
        }

        $sales = $query->get();

        if ($sales->isEmpty()) {
            $this->error('❌ Không tìm thấy đơn hàng bán nào khớp với tiêu chí đã chọn.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->comment('🧪 CHẾ ĐỘ CHẠY THỬ (DRY-RUN): Sẽ không có thay đổi nào được lưu vào database.');
            $this->newLine();
        }

        $this->info('Danh sách đơn hàng bán sẽ xử lý:');
        $headers = ['ID', 'Mã Đơn', 'Khách Hàng', 'Ngày Tạo', 'Trạng Thái', 'Nguồn Báo Giá'];
        $rows = [];

        foreach ($sales as $sale) {
            $quotation = Quotation::where('converted_to_sale_id', $sale->id)->first();
            $rows[] = [
                $sale->id,
                $sale->code,
                $sale->customer_name,
                $sale->date ? $sale->date->format('d/m/Y') : '-',
                $sale->status,
                $quotation ? "Có ({$quotation->code})" : 'Không'
            ];
        }
        $this->table($headers, $rows);
        $this->newLine();

        if (!$force && !$dryRun) {
            $confirm = $this->readConsoleInput('Bạn có chắc chắn muốn cập nhật VAT cho ' . $sales->count() . ' đơn hàng trên không? (yes/no) [no]: ');
            if (!in_array(strtolower(trim($confirm)), ['yes', 'y'])) {
                $this->warn('❌ Đã hủy thao tác.');
                return self::SUCCESS;
            }
        }

        $currencyService = app(CurrencyService::class);
        $updatedCount = 0;

        foreach ($sales as $sale) {
            $this->info("----------------------------------------------------------------");
            $this->info("-> Đang xử lý đơn hàng {$sale->code} (ID: {$sale->id})...");

            $quotation = Quotation::where('converted_to_sale_id', $sale->id)->with('items')->first();
            
            $saleItems = $sale->items;
            if ($saleItems->isEmpty()) {
                $this->warn("   ⚠ Đơn hàng không có sản phẩm nào. Bỏ qua.");
                continue;
            }

            // Matching logic if converted from quotation
            $itemMappings = [];
            if ($quotation) {
                $this->comment("   📝 Tìm thấy báo giá gốc: {$quotation->code}. Tiến hành so khớp sản phẩm...");
                $quotationItems = $quotation->items;
                $matchedQuotationItemIds = [];

                foreach ($saleItems as $saleItem) {
                    $matchedItem = null;

                    // 1. Exact match
                    if ($saleItem->product_id) {
                        $matchedItem = $quotationItems->first(function ($qItem) use ($saleItem, $matchedQuotationItemIds) {
                            return !in_array($qItem->id, $matchedQuotationItemIds)
                                && $qItem->product_id == $saleItem->product_id
                                && $qItem->product_name == $saleItem->product_name
                                && $qItem->quantity == $saleItem->quantity
                                && $qItem->price == $saleItem->price;
                        });
                    }

                    // 2. Product ID match
                    if (!$matchedItem && $saleItem->product_id) {
                        $matchedItem = $quotationItems->first(function ($qItem) use ($saleItem, $matchedQuotationItemIds) {
                            return !in_array($qItem->id, $matchedQuotationItemIds)
                                && $qItem->product_id == $saleItem->product_id;
                        });
                    }

                    // 3. Product name match
                    if (!$matchedItem) {
                        $matchedItem = $quotationItems->first(function ($qItem) use ($saleItem, $matchedQuotationItemIds) {
                            return !in_array($qItem->id, $matchedQuotationItemIds)
                                && $qItem->product_name == $saleItem->product_name;
                        });
                    }

                    if ($matchedItem) {
                        $matchedQuotationItemIds[] = $matchedItem->id;
                        $itemMappings[$saleItem->id] = $matchedItem;
                    }
                }

                // 4. Index-based fallback if count matches
                if (count($saleItems) === count($quotationItems)) {
                    for ($i = 0; $i < count($saleItems); $i++) {
                        if (!isset($itemMappings[$saleItems[$i]->id])) {
                            $itemMappings[$saleItems[$i]->id] = $quotationItems[$i];
                        }
                    }
                }
            }

            $subtotalForeign = 0;
            $totalVatAmountForeign = 0;
            $firstVat = null;
            $allSameVat = true;
            
            $itemDetailsTable = [];

            // Begin recalculation per item
            foreach ($saleItems as $item) {
                $itemSubtotal = round($item->quantity * $item->price, 2);
                $subtotalForeign += $itemSubtotal;

                // Determine VAT rate
                $oldVat = $item->vat;
                $oldVatAmount = $item->vat_amount;
                $itemVatRate = 0.0;

                if (isset($itemMappings[$item->id])) {
                    $qItem = $itemMappings[$item->id];
                    $itemVatRate = (float) $qItem->vat;
                    $source = "Báo giá ({$qItem->vat}%)";
                } elseif ($item->vat !== null && (float)$item->vat != 0) {
                    $itemVatRate = (float) $item->vat;
                    $source = "Giữ nguyên ({$item->vat}%)";
                } elseif ($sale->vat !== null && (float)$sale->vat != 0) {
                    $itemVatRate = (float) $sale->vat;
                    $source = "Từ header đơn hàng ({$sale->vat}%)";
                } elseif ($defaultVat !== null) {
                    $itemVatRate = $defaultVat;
                    $source = "Default option ({$defaultVat}%)";
                } else {
                    $itemVatRate = 0.0;
                    $source = "Mặc định (0%)";
                }

                // Calculate item VAT amount based on discount percentage of sale
                $itemDiscount = round($itemSubtotal * ($sale->discount ?? 0) / 100, 2);
                $effectiveVat = $itemVatRate < 0 ? 0 : $itemVatRate;
                $itemBaseForVat = $itemSubtotal - $itemDiscount;
                $itemVatAmount = round($itemBaseForVat * $effectiveVat / 100, 2);
                $totalVatAmountForeign += $itemVatAmount;

                if ($firstVat === null) {
                    $firstVat = $itemVatRate;
                } elseif ($firstVat !== $itemVatRate) {
                    $allSameVat = false;
                }

                // Record details for table display
                $itemDetailsTable[] = [
                    $item->product_name ?: 'Sản phẩm không tên',
                    $item->quantity,
                    number_format((float)$item->price) . ' ' . ($sale->currency ? $sale->currency->code : 'VND'),
                    "{$oldVat}% -> {$itemVatRate}% ({$source})",
                    number_format((float)$oldVatAmount, 2) . ' -> ' . number_format($itemVatAmount, 2)
                ];

                if (!$dryRun) {
                    $item->vat = $itemVatRate;
                    $item->vat_amount = $itemVatAmount;
                    $item->save();
                }
            }

            // Show items details table
            $this->table(['Tên Sản Phẩm', 'SL', 'Đơn Giá', 'VAT % Thay Đổi', 'VAT Amount Thay Đổi'], $itemDetailsTable);

            // Calculate header values
            $discountAmountForeign = round($subtotalForeign * ($sale->discount ?? 0) / 100, 2);
            $totalForeign = round($subtotalForeign - $discountAmountForeign + $totalVatAmountForeign, 2);

            $isForeign = $currencyService->isForeignTransaction($sale->currency_id);
            $exchangeRate = (float) ($sale->exchange_rate ?: 1);

            $subtotalVnd = $isForeign ? $currencyService->toBase($subtotalForeign, $exchangeRate) : $subtotalForeign;
            $vatAmountVnd = $isForeign ? $currencyService->toBase($totalVatAmountForeign, $exchangeRate) : $totalVatAmountForeign;
            $totalVnd = $isForeign ? $currencyService->toBase($totalForeign, $exchangeRate) : $totalForeign;

            $representativeVat = $allSameVat ? ($firstVat ?? 0) : ($firstVat ?? 0);

            // Keep original values for summary
            $oldTotalVnd = $sale->total;
            $oldVatAmountVnd = $sale->vat_amount;
            $oldVatHeader = $sale->vat;
            $oldDebtVnd = $sale->debt_amount;

            if (!$dryRun) {
                DB::transaction(function() use ($sale, $subtotalVnd, $representativeVat, $vatAmountVnd, $totalVnd, $totalForeign) {
                    $sale->subtotal = $subtotalVnd;
                    $sale->vat = $representativeVat;
                    $sale->vat_amount = $vatAmountVnd;
                    $sale->total = $totalVnd;
                    $sale->total_foreign = $totalForeign;
                    
                    // Recalculate margins and debts in-memory
                    $sale->calculateMargin();
                    $sale->updateDebt();
                    
                    $sale->save();
                });
            } else {
                // Simulate in-memory changes for summary output
                $sale->subtotal = $subtotalVnd;
                $sale->vat = $representativeVat;
                $sale->vat_amount = $vatAmountVnd;
                $sale->total = $totalVnd;
                $sale->total_foreign = $totalForeign;
                $sale->calculateMargin();
                $sale->updateDebt();
            }

            // Print summary of changes
            $this->comment("   📊 Tổng kết thay đổi cho đơn hàng {$sale->code}:");
            $this->line("     - Header VAT %:   {$oldVatHeader}% -> {$sale->vat}%");
            $this->line("     - VAT VND:        " . number_format((float)$oldVatAmountVnd) . " đ -> " . number_format((float)$sale->vat_amount) . " đ");
            $this->line("     - Tổng tiền VND:  " . number_format((float)$oldTotalVnd) . " đ -> " . number_format((float)$sale->total) . " đ");
            $this->line("     - Công nợ VND:    " . number_format((float)$oldDebtVnd) . " đ -> " . number_format((float)$sale->debt_amount) . " đ");
            $this->line("     - Trạng thái TT:  {$sale->payment_status}");
            
            $updatedCount++;
        }

        $this->info('================================================================');
        if ($dryRun) {
            $this->info("🎉 CHẠY THỬ HOÀN TẤT: Đã mô phỏng tính toán lại VAT cho {$updatedCount} đơn hàng.");
        } else {
            $this->info("🎉 THÀNH CÔNG: Đã cập nhật và tính toán lại VAT cho {$updatedCount} đơn hàng!");
        }
        $this->info('================================================================');

        return self::SUCCESS;
    }

    /**
     * Đọc input từ console có hỗ trợ block và kiểm tra readline
     */
    private function readConsoleInput(string $prompt): string
    {
        if (function_exists('readline')) {
            $input = readline($prompt);
            if ($input !== false) {
                return $input;
            }
        }
        
        $this->output->write($prompt);
        @stream_set_blocking(STDIN, true);
        return fgets(STDIN) ?: '';
    }
}

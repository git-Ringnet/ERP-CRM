<?php

namespace App\Console\Commands;

use App\Models\Export;
use App\Models\ExportItem;
use App\Models\ProductItem;
use App\Models\ProductItemBorrowLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSwappedExportSerial extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:fix-swapped-serial
                            {export : Mã phiếu xuất đã duyệt (VD EXP00048)}
                            {wrong_sku : S/N hệ thống ghi nhận sai (thực tế vẫn còn trong kho)}
                            {actual_sku : S/N kho thực tế đã giao cho khách}
                            {--dry-run : Chỉ liệt kê, không cập nhật}
                            {--force : Không hỏi xác nhận}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sửa phiếu xuất bị đồng bộ đổi S/N: ghi nhận đúng S/N đã giao, trả S/N ghi sai về kho kèm suất giữ của S/N đã giao.';

    public function handle(): int
    {
        $export = Export::where('code', $this->argument('export'))->first();
        if (!$export || $export->status !== 'completed') {
            $this->error('Không tìm thấy phiếu xuất đã duyệt ' . $this->argument('export') . '.');
            return self::FAILURE;
        }

        $wrong = ProductItem::where('sku', $this->argument('wrong_sku'))->where('export_id', $export->id)->first();
        if (!$wrong || $wrong->status !== ProductItem::STATUS_SOLD) {
            $this->error("S/N {$this->argument('wrong_sku')} không phải thiết bị đã xuất theo phiếu {$export->code}.");
            return self::FAILURE;
        }

        $actual = ProductItem::where('sku', $this->argument('actual_sku'))
            ->where('product_id', $wrong->product_id)
            ->where('warehouse_id', $wrong->warehouse_id)
            ->first();
        if (!$actual || $actual->status !== ProductItem::STATUS_IN_STOCK) {
            $this->error("S/N {$this->argument('actual_sku')} không còn trong kho / khác mã hàng hoặc kho với {$wrong->sku}.");
            return self::FAILURE;
        }

        $exportItem = ExportItem::where('export_id', $export->id)
            ->where('product_id', $wrong->product_id)
            ->get()
            ->first(fn ($item) => in_array($wrong->id, array_map('intval', json_decode((string) $item->serial_number, true) ?: []), true));

        // Phiếu chưa hoàn thành khác đang chọn S/N đã giao: phải gỡ, nếu không lúc duyệt sẽ xuất trùng
        $otherLines = ExportItem::with('export:id,code,status')
            ->where('export_id', '!=', $export->id)
            ->whereNotNull('serial_number')
            ->whereHas('export', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->get()
            ->filter(fn ($item) => in_array($actual->id, array_map('intval', json_decode((string) $item->serial_number, true) ?: []), true));

        $holder = trim((string) $actual->borrower);

        $this->table(['Thay đổi', 'Chi tiết'], array_filter([
            ["{$actual->sku} (đã giao)", "in_stock → sold, phiếu {$export->code}" . ($holder ? ", bỏ người giữ {$holder}" : '')],
            ["{$wrong->sku} (ghi sai)", 'sold → in_stock' . ($holder ? ", người giữ {$holder} (nhận lại suất giữ)" : ', không ai giữ')],
            $exportItem ? ["Dòng phiếu {$export->code}", "{$exportItem->serial_number} → thay {$wrong->id} bằng {$actual->id}"] : null,
            ...$otherLines->map(fn ($item) => ["Phiếu {$item->export->code} ({$item->export->status})", "gỡ {$actual->sku} khỏi {$item->serial_number}"])->all(),
        ]));

        if ($this->option('dry-run')) {
            $this->info('Dry run: chưa cập nhật gì.');
            return self::SUCCESS;
        }
        if (!$this->option('force') && !$this->confirm('Thực hiện cập nhật?')) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($export, $wrong, $actual, $exportItem, $otherLines, $holder) {
            $note = "Sửa phiếu {$export->code}: kho đã giao {$actual->sku}, hệ thống ghi nhầm {$wrong->sku}";

            $actual->update(['status' => ProductItem::STATUS_SOLD, 'export_id' => $export->id, 'borrower' => null]);
            $wrong->update(['status' => ProductItem::STATUS_IN_STOCK, 'export_id' => null, 'borrower' => $holder ?: null]);

            if ($holder) {
                $holderId = User::where('name', $holder)->value('id');
                ProductItemBorrowLog::create([
                    'product_item_id' => $actual->id,
                    'from_user_id' => $holderId,
                    'to_user_id' => null,
                    'ticket_id' => null,
                    'note' => mb_substr("{$note}; chuyển suất giữ của {$holder} sang {$wrong->sku}", 0, 255),
                ]);
                ProductItemBorrowLog::create([
                    'product_item_id' => $wrong->id,
                    'from_user_id' => null,
                    'to_user_id' => $holderId,
                    'ticket_id' => null,
                    'note' => mb_substr("{$note}; {$holder} giữ {$wrong->sku} thay cho {$actual->sku}", 0, 255),
                ]);
            }

            if ($exportItem) {
                $ids = array_map('intval', json_decode((string) $exportItem->serial_number, true) ?: []);
                $ids = array_map(fn ($id) => $id === (int) $wrong->id ? (int) $actual->id : $id, $ids);
                $exportItem->update(['serial_number' => json_encode(array_values($ids))]);
            }

            foreach ($otherLines as $item) {
                $ids = array_values(array_diff(array_map('intval', json_decode((string) $item->serial_number, true) ?: []), [(int) $actual->id]));
                $item->update(['serial_number' => empty($ids) ? null : json_encode($ids)]);
            }

            app(\App\Services\ActivityLogService::class)->log('updated', $export, [
                'wrong_sku' => $wrong->sku,
                'actual_sku' => $actual->sku,
                'hold_moved_to_wrong_sku' => $holder ?: null,
                'removed_from_exports' => $otherLines->map(fn ($item) => $item->export->code)->values()->all(),
            ], $note);
        });

        $this->info('Đã cập nhật.');
        return self::SUCCESS;
    }
}

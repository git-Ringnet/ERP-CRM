<?php

namespace App\Console\Commands;

use App\Models\ExportItem;
use App\Models\Import;
use App\Models\ProductItem;
use App\Models\PurchaseOrder;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dọn dữ liệu do lỗi import serial cũ: mã trong file được ghép "gần đúng" (mã này chứa mã kia)
 * nên serial của "FG-60F-BDL-950-12" bị gán cả vào dòng "FG-60F" và ngược lại; khi nhận hàng
 * mỗi dòng tạo thiết bị theo toàn bộ danh sách serial → kho dư thiết bị ảo, serial trùng giữa các mã.
 *
 * Với mỗi nhóm dòng PO dùng chung serial: serial của mã P đã bị ghép vào đúng tập dòng
 * {L : P "khớp gần đúng" L}, nên dựa vào tập dòng đang chứa serial để biết serial thuộc mã nào.
 * Nhóm có 2 mã cho cùng tập dòng (VD chỉ có FG-40F và FG-40F-BDL) không tách được → cần file --map.
 */
class FixCrossAssignedPoSerials extends Command
{
    protected $signature = 'purchase-orders:fix-cross-assigned-serials
                            {--po=* : Chỉ xử lý các mã PO này (mặc định: tự tìm mọi PO bị lỗi)}
                            {--map= : File CSV (serial,mã sản phẩm) cho các nhóm không tự tách được}
                            {--export-template= : Ghi file CSV các serial cần kho xác định mã}
                            {--dry-run : Chỉ phân tích, không cập nhật}
                            {--restore= : Hoàn tác một lần chạy trước từ file sao lưu (storage/app/backups/...)}
                            {--force : Không hỏi xác nhận}';

    protected $description = 'Dọn thiết bị ảo / serial gán chéo giữa các dòng PO do lỗi import serial ghép mã gần đúng.';

    private array $report = [];
    private array $templateRows = [];

    public function handle(InventoryService $inventoryService): int
    {
        $this->report = [];
        $this->templateRows = [];
        if ($restoreFile = $this->option('restore')) {
            return $this->restore($restoreFile, $inventoryService);
        }
        $map = $this->loadMap();
        $pos = $this->affectedPurchaseOrders();
        if ($pos->isEmpty()) {
            $this->info('Không có PO nào bị gán serial chéo giữa các dòng.');
            return self::SUCCESS;
        }

        $plans = [];
        foreach ($pos as $po) {
            foreach ($this->components($po) as $lines) {
                $plan = $this->planComponent($po, $lines, $map);
                if ($plan) {
                    $plans[] = $plan;
                }
            }
        }

        $this->table(['PO', 'Nhóm mã', 'Kết quả phân tích'], $this->report);

        if ($path = $this->option('export-template')) {
            $fh = fopen($path, 'w');
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, ['PO', 'Serial', 'Mã sản phẩm (điền 1 trong các mã gợi ý)', 'Mã gợi ý']);
            foreach ($this->templateRows as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
            $this->info('Đã ghi file cần kho xác định mã: ' . $path . ' (' . count($this->templateRows) . ' serial)');
        }

        if (empty($plans)) {
            $this->warn('Không có nhóm nào đủ điều kiện để sửa.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(['PO', 'Mã', 'SL đặt', 'Thiết bị hiện có', 'Giữ lại đúng', 'Đổi serial', 'Xóa (ảo)', 'Tạo thêm'],
            collect($plans)->flatMap(fn ($p) => $p['summary'])->all());

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN: chưa cập nhật. Chạy lại không có --dry-run để áp dụng.');
            return self::SUCCESS;
        }
        if (!$this->option('force') && !$this->confirm('Áp dụng các thay đổi trên?', false)) {
            $this->warn('Đã huỷ, không thay đổi dữ liệu.');
            return self::SUCCESS;
        }

        // Sao lưu nguyên trạng mọi bản ghi sẽ bị đụng tới để có thể hoàn tác (--restore)
        $backup = $this->buildBackup($plans);

        $productIds = [];
        DB::transaction(function () use ($plans, &$productIds, &$backup) {
            foreach ($plans as $plan) {
                foreach ($plan['actions'] as $a) {
                    $backup['created_ids'] = array_merge($backup['created_ids'], $this->applyLine($a));
                    $productIds[$a['product_id']] = true;
                }
            }
        });
        $backupPath = 'backups/fix-cross-serials-' . now()->format('Ymd_His') . '.json';
        \Illuminate\Support\Facades\Storage::put($backupPath, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        foreach (array_keys($productIds) as $pid) {
            $inventoryService->resyncStockFromItems($pid);
        }

        $this->info('✅ Đã dọn ' . count($plans) . ' nhóm mã và đồng bộ lại tồn kho.');
        $this->info('Sao lưu trước khi sửa: storage/app/' . $backupPath);
        $this->info('Hoàn tác nếu cần: php artisan purchase-orders:fix-cross-assigned-serials --restore=' . $backupPath);
        return self::SUCCESS;
    }

    private function buildBackup(array $plans): array
    {
        $itemIds = [];
        $poItems = [];
        foreach ($plans as $plan) {
            foreach ($plan['actions'] as $a) {
                $poItems[$a['po_item']->id] = $a['po_item']->serial_number;
                foreach ($a['delete'] as $i) { $itemIds[] = $i->id; }
                foreach ($a['relabel'] as $r) { $itemIds[] = $r['item']->id; }
                foreach ($a['hold_moves'] as $m) { $itemIds[] = $m['from']->id; $itemIds[] = $m['to']->id; }
            }
        }
        $itemIds = array_values(array_unique($itemIds));

        return [
            'created_at' => now()->toDateTimeString(),
            'product_items' => DB::table('product_items')->whereIn('id', $itemIds)->get()->map(fn ($r) => (array) $r)->all(),
            'borrow_logs' => DB::table('product_item_borrow_logs')->whereIn('product_item_id', $itemIds)->get()->map(fn ($r) => (array) $r)->all(),
            'po_items' => $poItems,
            'created_ids' => [],
        ];
    }

    private function restore(string $file, InventoryService $inventoryService): int
    {
        $path = str_starts_with($file, 'backups/') ? $file : 'backups/' . basename($file);
        if (!\Illuminate\Support\Facades\Storage::exists($path)) {
            $this->error("Không tìm thấy file sao lưu: storage/app/{$path}");
            return self::FAILURE;
        }
        $backup = json_decode(\Illuminate\Support\Facades\Storage::get($path), true);
        $items = collect($backup['product_items'] ?? []);
        $this->info("Hoàn tác lần chạy lúc {$backup['created_at']}: khôi phục {$items->count()} thiết bị, xóa " . count($backup['created_ids'] ?? []) . ' thiết bị tạo thêm, ' . count($backup['po_items'] ?? []) . ' dòng PO.');
        if (!$this->option('force') && !$this->confirm('Xác nhận hoàn tác?', false)) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($backup, $items) {
            $ids = $items->pluck('id')->all();
            DB::table('product_items')->whereIn('id', $backup['created_ids'] ?? [])->delete();
            // Đổi tạm serial các bản ghi còn tồn tại để tránh vướng khóa (product_id, sku) khi trả lại serial cũ
            DB::table('product_items')->whereIn('id', $ids)->update(['sku' => DB::raw("CONCAT('TMPRESTORE_', id)")]);
            foreach ($items as $row) {
                DB::table('product_items')->updateOrInsert(['id' => $row['id']], $row);
            }
            DB::table('product_item_borrow_logs')->whereIn('product_item_id', $ids)->delete();
            foreach ($backup['borrow_logs'] ?? [] as $log) {
                DB::table('product_item_borrow_logs')->updateOrInsert(['id' => $log['id']], $log);
            }
            foreach ($backup['po_items'] ?? [] as $poItemId => $serials) {
                DB::table('purchase_order_items')->where('id', $poItemId)->update(['serial_number' => $serials]);
            }
        });
        foreach ($items->pluck('product_id')->unique() as $pid) {
            $inventoryService->resyncStockFromItems((int) $pid);
        }
        $this->info('✅ Đã hoàn tác và đồng bộ lại tồn kho.');

        return self::SUCCESS;
    }

    /** PO có ít nhất một serial nằm trên nhiều dòng */
    private function affectedPurchaseOrders(): Collection
    {
        $query = PurchaseOrder::with('items.product')->where('status', '!=', 'cancelled');
        if ($codes = $this->option('po')) {
            $query->whereIn('code', $codes);
        }

        return $query->get()->filter(function ($po) {
            $seen = [];
            foreach ($po->items as $item) {
                foreach ($this->serialsOf($item) as $sn) {
                    if (isset($seen[$sn])) {
                        return true;
                    }
                    $seen[$sn] = true;
                }
            }
            return false;
        })->values();
    }

    /** Các nhóm dòng PO dùng chung serial (thành phần liên thông) */
    private function components(PurchaseOrder $po): array
    {
        $lines = $po->items->filter(fn ($i) => !empty($this->serialsOf($i)))->keyBy('id');
        $owner = [];
        $parent = $lines->keys()->mapWithKeys(fn ($id) => [$id => $id])->all();
        $find = function ($x) use (&$parent, &$find) {
            return $parent[$x] === $x ? $x : ($parent[$x] = $find($parent[$x]));
        };
        foreach ($lines as $item) {
            foreach ($this->serialsOf($item) as $sn) {
                if (isset($owner[$sn])) {
                    $parent[$find($item->id)] = $find($owner[$sn]);
                } else {
                    $owner[$sn] = $item->id;
                }
            }
        }
        $groups = [];
        foreach ($lines as $item) {
            $groups[$find($item->id)][] = $item;
        }

        return array_values(array_filter($groups, fn ($g) => count($g) > 1));
    }

    private function planComponent(PurchaseOrder $po, array $lines, array $map): ?array
    {
        $codes = array_map(fn ($l) => $this->codeOf($l), $lines);
        $label = implode(' / ', $codes);

        // Tập dòng mà serial của từng mã (= mã của 1 dòng) đã bị ghép vào theo quy tắc cũ
        $expected = [];
        foreach ($lines as $p) {
            $sig = [];
            foreach ($lines as $l) {
                if ($this->oldFuzzyMatch($this->codeOf($l), $this->codeOf($p))) {
                    $sig[] = $l->id;
                }
            }
            sort($sig);
            $expected[$p->id] = implode(',', $sig);
        }
        $ambiguous = count(array_unique($expected)) < count($expected);

        // Các mã không "chứa nhau" (VD 2 license khác nhau cho cùng 1 thiết bị) → dùng chung serial là hợp lệ
        if (collect($lines)->every(fn ($l) => $expected[$l->id] === (string) $l->id)) {
            $this->report[] = [$po->code, $label, 'Không phải lỗi: các mã khác nhau dùng chung serial thiết bị (VD license) – giữ nguyên'];
            return null;
        }

        // Tập dòng thực tế đang chứa từng serial
        $actual = [];
        foreach ($lines as $l) {
            foreach ($this->serialsOf($l) as $sn) {
                $actual[$sn][] = $l->id;
            }
        }

        $lineByCode = [];
        foreach ($lines as $l) {
            $lineByCode[strtoupper($this->codeOf($l))] = $l;
        }

        $assign = []; // line_id => [serials]
        $unresolved = [];
        foreach ($actual as $sn => $lineIds) {
            if (isset($map[$sn]) && isset($lineByCode[$map[$sn]])) {
                $assign[$lineByCode[$map[$sn]]->id][] = $sn;
                continue;
            }
            sort($lineIds);
            $sig = implode(',', $lineIds);
            $candidates = array_keys(array_filter($expected, fn ($e) => $e === $sig));
            if (count($candidates) === 1) {
                $assign[$candidates[0]][] = $sn;
            } else {
                $unresolved[] = $sn;
                $suggest = $candidates ? array_map(fn ($id) => $this->codeOf(collect($lines)->firstWhere('id', $id)), $candidates) : $codes;
                $this->templateRows[] = [$po->code, $sn, '', implode(' | ', $suggest)];
            }
        }

        if (!empty($unresolved)) {
            $this->report[] = [$po->code, $label, ($ambiguous ? 'Không tự tách được' : 'Có serial không xác định') . ' – còn ' . count($unresolved) . ' serial cần kho xác định mã (dùng --export-template / --map)'];
            return null;
        }

        // Số serial mỗi mã phải đúng số lượng đặt, nếu không thì không chắc chắn → bỏ qua
        foreach ($lines as $l) {
            $got = count($assign[$l->id] ?? []);
            if ($got !== (int) $l->quantity) {
                $this->report[] = [$po->code, $label, "Bỏ qua: mã {$this->codeOf($l)} xác định được {$got} serial nhưng PO đặt {$l->quantity}"];
                return null;
            }
        }

        $importIds = Import::where('reference_type', 'purchase_order')->where('reference_id', $po->id)->pluck('id')->all();
        $reservedInExports = $this->itemIdsInOpenExports();

        $actions = [];
        $summary = [];
        foreach ($lines as $l) {
            $correct = $assign[$l->id];
            $items = ProductItem::whereIn('import_id', $importIds)->where('product_id', $l->product_id)->orderBy('id')->get();

            $locked = $items->filter(fn ($i) => $i->status !== ProductItem::STATUS_IN_STOCK || in_array($i->id, $reservedInExports, true));
            $badLocked = $locked->reject(fn ($i) => in_array($i->sku, $correct, true));
            if ($badLocked->isNotEmpty()) {
                $this->report[] = [$po->code, $label, "Bỏ qua: mã {$this->codeOf($l)} có thiết bị sai serial đã xuất/đang trong phiếu xuất (" . $badLocked->pluck('sku')->implode(', ') . ')'];
                return null;
            }

            $kept = $items->filter(fn ($i) => in_array($i->sku, $correct, true))->unique('sku');
            $others = $items->reject(fn ($i) => $kept->contains('id', $i->id))
                ->sortByDesc(fn ($i) => !empty($i->borrower)) // ưu tiên giữ lại thiết bị đang có người giữ
                ->values();
            $missing = array_values(array_diff($correct, $kept->pluck('sku')->all()));

            $relabel = [];
            foreach ($missing as $idx => $sn) {
                if (isset($others[$idx])) {
                    $relabel[] = ['item' => $others[$idx], 'sku' => $sn];
                }
            }
            $toDelete = $others->slice(count($relabel))->values();
            $toCreate = array_slice($missing, count($relabel));

            // Người đang giữ trên thiết bị sắp xóa → chuyển sang thiết bị còn lại chưa ai giữ
            $holdMoves = [];
            $freeKeep = $kept->concat(collect($relabel)->pluck('item'))->filter(fn ($i) => empty($i->borrower))->values();
            foreach ($toDelete->filter(fn ($i) => !empty($i->borrower)) as $idx => $held) {
                $target = $freeKeep->shift();
                if (!$target) {
                    $this->report[] = [$po->code, $label, "Bỏ qua: mã {$this->codeOf($l)} có nhiều thiết bị đang được giữ hơn số lượng đặt"];
                    return null;
                }
                $holdMoves[] = ['from' => $held, 'to' => $target];
            }

            $actions[] = [
                'po_item' => $l,
                'product_id' => (int) $l->product_id,
                'correct' => $correct,
                'relabel' => $relabel,
                'delete' => $toDelete,
                'create' => $toCreate,
                'create_from' => $items->first(),
                'hold_moves' => $holdMoves,
            ];
            $summary[] = [$po->code, $this->codeOf($l), (int) $l->quantity, $items->count(), $kept->count(), count($relabel), $toDelete->count(), count($toCreate)];
        }

        $this->report[] = [$po->code, $label, 'Tự xác định được, khớp đúng số lượng đặt' . ($map ? ' (có dùng file --map)' : '')];

        return compact('actions', 'summary');
    }

    /** @return int[] ID thiết bị được tạo thêm (để hoàn tác) */
    private function applyLine(array $a): array
    {
        $createdIds = [];
        foreach ($a['hold_moves'] as $mv) {
            $mv['to']->update(['borrower' => $mv['from']->borrower]);
            DB::table('product_item_borrow_logs')->where('product_item_id', $mv['from']->id)
                ->update(['product_item_id' => $mv['to']->id]);
        }

        foreach ($a['delete'] as $item) {
            $item->delete();
        }

        // Đổi serial qua mã tạm trước để không vướng khóa (product_id, sku)
        foreach ($a['relabel'] as $r) {
            $r['item']->update(['sku' => 'TMPFIX_' . $r['item']->id]);
        }
        foreach ($a['relabel'] as $r) {
            $r['item']->update(['sku' => $r['sku']]);
        }

        foreach ($a['create'] as $sn) {
            $base = $a['create_from'];
            $createdIds[] = ProductItem::create([
                'product_id' => $a['product_id'],
                'warehouse_id' => $base?->warehouse_id,
                'import_id' => $base?->import_id,
                'sku' => $sn,
                'quantity' => 1,
                'cost_usd' => $base?->cost_usd ?? 0,
                'comments' => $base?->comments,
                'status' => ProductItem::STATUS_IN_STOCK,
            ])->id;
        }

        $a['po_item']->update(['serial_number' => json_encode(array_values($a['correct']))]);

        return $createdIds;
    }

    private function itemIdsInOpenExports(): array
    {
        return ExportItem::whereNotNull('serial_number')
            ->whereHas('export', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->pluck('serial_number')
            ->flatMap(fn ($j) => array_map('intval', json_decode((string) $j, true) ?: []))
            ->unique()->values()->all();
    }

    private function loadMap(): array
    {
        $path = $this->option('map');
        if (!$path) {
            return [];
        }
        $map = [];
        $fh = fopen($path, 'r');
        while (($row = fgetcsv($fh)) !== false) {
            // Hỗ trợ file mẫu (PO, Serial, Mã, Gợi ý) và file 2 cột (Serial, Mã)
            [$sn, $code] = count($row) >= 3 ? [$row[1] ?? '', $row[2] ?? ''] : [$row[0] ?? '', $row[1] ?? ''];
            $sn = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $sn));
            $code = strtoupper(trim((string) $code));
            if ($sn !== '' && $code !== '' && !str_contains(strtolower($sn), 'serial')) {
                $map[$sn] = $code;
            }
        }
        fclose($fh);
        $this->info('Đã đọc ' . count($map) . ' serial từ file ' . $path);

        return $map;
    }

    private function serialsOf($item): array
    {
        return array_values(array_unique(array_filter(json_decode((string) $item->serial_number, true) ?: [])));
    }

    private function codeOf($item): string
    {
        return (string) ($item->product->code ?? $item->product_name);
    }

    /** Quy tắc ghép mã của bản import cũ: trùng sau chuẩn hóa hoặc mã này chứa mã kia (>= 4 ký tự) */
    private function oldFuzzyMatch(string $lineCode, string $excelCode): bool
    {
        $a = preg_replace('/[^A-Z0-9]/', '', strtoupper($lineCode));
        $b = preg_replace('/[^A-Z0-9]/', '', strtoupper($excelCode));
        if ($a === $b) {
            return true;
        }

        return strlen($a) >= 4 && strlen($b) >= 4 && (str_contains($a, $b) || str_contains($b, $a));
    }
}

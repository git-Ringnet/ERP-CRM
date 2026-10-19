<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\SupplierPriceList;
use App\Models\SupplierPriceListItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProductDescriptionFromPriceList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-description-from-price-list
                            {--supplier=Fortinet : Tên hãng/NCC của bảng giá (so khớp gần đúng)}
                            {--price-list= : Chỉ dùng 1 bảng giá theo ID (bỏ qua --supplier)}
                            {--dry-run : Chỉ quét thống kê, không thực hiện cập nhật}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cập nhật Mô tả trong danh mục sản phẩm = Tên sản phẩm (mô tả đầy đủ của hãng) cho các mã có trong bảng giá NCC.';

    /** Mã bảng giá Fortinet dạng "...-DD" (DD = số tháng) ứng với các mã thực tế này */
    private const DD_TERMS = ['12', '24', '36', '48', '60'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($this->option('price-list')) {
            $priceListIds = SupplierPriceList::whereKey($this->option('price-list'))->pluck('id');
        } else {
            $supplier = trim((string) $this->option('supplier'));
            $priceListIds = SupplierPriceList::whereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$supplier}%"))->pluck('id');
        }

        if ($priceListIds->isEmpty()) {
            $this->error('Không tìm thấy bảng giá phù hợp.');
            return self::FAILURE;
        }

        $this->info('Bảng giá sử dụng: #' . $priceListIds->implode(', #'));
        if ($dryRun) {
            $this->warn('🔍 DRY-RUN: chỉ thống kê, KHÔNG cập nhật CSDL.');
        }

        // Gom toàn bộ mã sản phẩm có trong bảng giá (mở rộng mã -DD thành -12/-24/-36/-48/-60)
        $codes = [];
        SupplierPriceListItem::whereIn('supplier_price_list_id', $priceListIds)
            ->select('id', 'sku')
            ->chunkById(2000, function ($items) use (&$codes) {
                foreach ($items as $item) {
                    $sku = trim((string) $item->sku);
                    if ($sku === '') {
                        continue;
                    }
                    $codes[$sku] = true;
                    if (str_ends_with(strtoupper($sku), '-DD')) {
                        $prefix = substr($sku, 0, -2);
                        foreach (self::DD_TERMS as $term) {
                            $codes[$prefix . $term] = true;
                        }
                    }
                }
            });
        $codes = array_keys($codes);
        $this->info('Số mã trong bảng giá (đã mở rộng mã -DD): ' . number_format(count($codes)));

        $matched = 0;
        $toUpdate = 0;
        $updated = 0;
        $samples = [];

        foreach (array_chunk($codes, 1000) as $chunk) {
            $matched += Product::whereIn('code', $chunk)->count();

            // Chỉ cập nhật khi Tên SP có nội dung thật (không phải chính mã SP) và Mô tả đang khác Tên
            $query = Product::whereIn('code', $chunk)
                ->whereNotNull('name')
                ->where('name', '<>', '')
                ->whereColumn('name', '<>', 'code')
                ->where(fn ($q) => $q->whereNull('description')->orWhereColumn('description', '<>', 'name'));

            $count = (clone $query)->count();
            $toUpdate += $count;

            if ($count > 0 && count($samples) < 10) {
                foreach ((clone $query)->limit(10 - count($samples))->get(['code', 'name', 'description']) as $p) {
                    $samples[] = [$p->code, mb_strimwidth((string) $p->description, 0, 40, '…'), mb_strimwidth((string) $p->name, 0, 70, '…')];
                }
            }

            if (!$dryRun && $count > 0) {
                $updated += $query->update(['description' => DB::raw('name'), 'updated_at' => now()]);
            }
        }

        $this->newLine();
        $this->table(['Mã SP', 'Mô tả hiện tại', 'Mô tả mới (= Tên SP)'], $samples);
        $this->info('Sản phẩm trong danh mục khớp với bảng giá: ' . number_format($matched));
        $this->info('Sản phẩm cần cập nhật Mô tả: ' . number_format($toUpdate));

        if ($dryRun) {
            $this->warn('DRY-RUN: chưa cập nhật. Chạy lại không có --dry-run để áp dụng.');
        } else {
            $this->info('✅ Đã cập nhật Mô tả cho ' . number_format($updated) . ' sản phẩm.');
        }

        return self::SUCCESS;
    }
}

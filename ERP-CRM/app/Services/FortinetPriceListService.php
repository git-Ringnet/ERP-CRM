<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\SupplierPriceList;
use App\Models\SupplierPriceListItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Tra giá PriceList USD của Fortinet (FTN) trong bảng giá đã upload để đối chiếu với P&L.
 *
 * Quy ước SKU Fortinet: mã hàng dịch vụ có đuôi số tháng (VD: FG-120G-BDL-950-12) còn
 * bảng giá ghi đuôi "-DD" với giá theo năm ở các cột price_1yr..price_5yr
 * (VD: FG-120G-BDL-950-DD, price_1yr). Mã thường thì so khớp trực tiếp.
 */
class FortinetPriceListService
{
    private const DURATION_COLUMNS = [
        '12' => 'price_1yr',
        '24' => 'price_2yr',
        '36' => 'price_3yr',
        '48' => 'price_4yr',
        '60' => 'price_5yr',
    ];

    private ?Collection $supplierIds = null;
    private ?Collection $allPriceLists = null;
    private array $cache = [];

    /** ID các nhà cung cấp Fortinet. */
    public function supplierIds(): Collection
    {
        return $this->supplierIds ??= Supplier::where('name', 'like', '%fortinet%')
            ->orWhere('code', 'like', '%FTN%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    public function isFortinetSupplier($supplierId): bool
    {
        return $supplierId !== null && $this->supplierIds()->contains((int) $supplierId);
    }

    /**
     * Tra giá USD của mã hàng trong bảng giá Fortinet có hiệu lực tại thời điểm $asOf
     * (VD: thời điểm gửi duyệt P&L). Không truyền $asOf = hôm nay.
     *
     * @return array{price: float, sku: string, price_list: string, currency: string}|null
     */
    public function lookup(?string $code, float $exchangeRate = 0, ?Carbon $asOf = null): ?array
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') {
            return null;
        }
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $cacheKey = $code . '|' . $asOf->toDateString();
        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $result = null;
        foreach ($this->priceListsValidAt($asOf) as $priceList) {
            // 1. Khớp đúng mã
            $item = SupplierPriceListItem::where('supplier_price_list_id', $priceList->id)->where('sku', $code)->first();
            $price = $item ? $priceList->getPrimaryPriceForItem($item) : null;

            // 2. Mã có đuôi số tháng -> SKU "-DD" + cột giá theo năm
            if (!$price && preg_match('/^(.*)-(12|24|36|48|60)$/', $code, $m)) {
                $item = SupplierPriceListItem::where('supplier_price_list_id', $priceList->id)->where('sku', $m[1] . '-DD')->first();
                $price = $item ? (float) ($item->{self::DURATION_COLUMNS[$m[2]]} ?? 0) : null;
            }

            if ($item && $price > 0) {
                $currency = strtoupper($priceList->currency ?? 'USD');
                if ($currency === 'VND' && $exchangeRate > 0) {
                    $price = $price / $exchangeRate;
                }
                $result = [
                    'price' => round((float) $price, 2),
                    'sku' => $item->sku,
                    'price_list' => $priceList->name,
                    'currency' => $currency,
                ];
                break;
            }
        }

        return $this->cache[$cacheKey] = $result;
    }

    /**
     * Bảng giá có hiệu lực tại ngày $asOf, mới nhất trước:
     * - Có khai báo ngày hiệu lực / hết hạn: ngày $asOf nằm trong khoảng đó (kể cả khi
     *   sau này bảng giá đã bị tắt), vì giá được xét theo thời điểm gửi duyệt.
     * - Không khai báo ngày nào: dùng cờ "Đang hiệu lực" (is_active).
     */
    private function priceListsValidAt(Carbon $asOf): Collection
    {
        $this->allPriceLists ??= SupplierPriceList::whereIn('supplier_id', $this->supplierIds())
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get();

        return $this->allPriceLists->filter(function (SupplierPriceList $list) use ($asOf) {
            $from = $list->effective_date ? Carbon::parse($list->effective_date)->startOfDay() : null;
            $to = $list->expiry_date ? Carbon::parse($list->expiry_date)->endOfDay() : null;

            if (!$from && !$to) {
                return (bool) $list->is_active;
            }

            return (!$from || $asOf->gte($from)) && (!$to || $asOf->lte($to));
        })->values();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleOrderRequestItem extends Model
{
    protected $fillable = [
        'sale_order_request_id',
        'sale_item_id',
        'vendor_id',
        'vendor',
        'type',
        'needs_cq',
        'part_number',
        'product_id',
        'quantity',
        'unit',
        'serial_number',
        'serial_expiry_dates',
        'exp_date',
        'si_name',
        'pos_id',
        'eu_name_mst',
        'address',
        'note',
        'is_cancelled',
        'cancel_reason',
    ];

    protected $casts = [
        'exp_date' => 'date',
        'serial_expiry_dates' => 'array',
        'is_cancelled' => 'boolean',
        'needs_cq' => 'boolean',
    ];

    public function saleOrderRequest(): BelongsTo
    {
        return $this->belongsTo(SaleOrderRequest::class, 'sale_order_request_id');
    }

    /**
     * Kiểm tra EU Name trước khi đặt hàng với hãng: phải là tiếng Anh và khớp tên tiếng Anh trên ĐKDA.
     *
     * @return array{level: string, message: string}|null  level: ok | warn
     */
    public function getEuCheckAttribute(): ?array
    {
        $euName = trim(explode(' - ', (string) $this->eu_name_mst)[0] ?? '');
        if ($euName === '') {
            return ['level' => 'warn', 'message' => 'Chưa có EU Name'];
        }

        $project = $this->saleItem?->project ?: $this->saleOrderRequest?->sale?->project;
        $projectEuEn = trim((string) ($project?->eu_name_en ?? ''));

        if (preg_match('/[^\x00-\x7F]/u', $euName)) {
            return ['level' => 'warn', 'message' => 'EU Name còn dấu tiếng Việt' . ($projectEuEn ? " – ĐKDA: {$projectEuEn}" : '')];
        }
        if ($projectEuEn !== '' && mb_strtolower($euName) !== mb_strtolower($projectEuEn)) {
            return ['level' => 'warn', 'message' => "Khác tên tiếng Anh trên ĐKDA: {$projectEuEn}"];
        }

        return ['level' => 'ok', 'message' => $projectEuEn !== '' ? 'Khớp ĐKDA (tiếng Anh)' : 'Tiếng Anh'];
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * % Margin (P&L) của đơn hàng bán. Lấy trực tiếp từ đơn (Sale::margin_percent) để không bị 0%
     * khi liên kết sale_item_id bị mất (đơn được sửa → dòng hàng bị xoá/tạo lại).
     */
    public function getMarginPercentAttribute(): float
    {
        $sale = $this->saleItem?->sale ?? $this->saleOrderRequest?->sale;

        return round($sale?->exactMarginPercent() ?? 0.0, 4);
    }

    /**
     * Nối lại sale_item_id cho các dòng Yêu cầu đặt hàng của đơn sau khi dòng hàng bán được tạo lại.
     * Khớp theo product_id, sau đó theo mã sản phẩm / part number; ưu tiên dòng hàng chưa được dùng.
     */
    public static function relinkForSale(Sale $sale): int
    {
        $saleItems = $sale->items()->with('product:id,code')->get();
        if ($saleItems->isEmpty()) {
            return 0;
        }

        $requestItems = static::whereHas('saleOrderRequest', fn ($q) => $q->where('sale_id', $sale->id))->get();
        $validIds = $saleItems->pluck('id')->all();
        $used = $requestItems->pluck('sale_item_id')->filter(fn ($id) => in_array($id, $validIds))->all();
        $normalize = fn ($v) => strtoupper(trim((string) $v));
        $fixed = 0;

        foreach ($requestItems as $ri) {
            if ($ri->sale_item_id && in_array($ri->sale_item_id, $validIds)) {
                continue;
            }

            $pn = $normalize($ri->part_number);
            $candidates = $saleItems->filter(function ($si) use ($ri, $pn, $normalize) {
                if ($ri->product_id && $si->product_id && (int) $ri->product_id === (int) $si->product_id) {
                    return true;
                }
                return $pn !== '' && ($normalize($si->product?->code) === $pn || $normalize($si->product_name) === $pn);
            });
            if ($candidates->isEmpty() && $saleItems->count() === 1) {
                $candidates = $saleItems;
            }

            $match = $candidates->first(fn ($si) => !in_array($si->id, $used)) ?? $candidates->first();
            if ($match) {
                $ri->sale_item_id = $match->id;
                $ri->saveQuietly();
                $used[] = $match->id;
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Sửa yêu cầu đặt hàng = xóa dòng cũ, tạo dòng mới. Trước khi xóa, chuyển liên kết của
     * các dòng PO (kể cả PO đã hủy / đã về) sang dòng mới tương ứng, tránh PO mất SO (hiện N/A)
     * và hàng đã đặt bị hiện lại ở Gom đơn như chưa đặt.
     *
     * Khớp: cùng hãng + cùng dòng P&L (sale_item_id) hoặc cùng mã / part number;
     * nhiều dòng cùng mã thì ưu tiên dòng có S/N trùng với S/N trên dòng PO / dòng cũ.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $oldItems
     * @param  \Illuminate\Support\Collection<int, self>  $newItems
     * @return int Số dòng PO đã chuyển liên kết
     */
    public static function carryPurchaseOrderLinks($oldItems, $newItems): int
    {
        if ($oldItems->isEmpty() || $newItems->isEmpty()) {
            return 0;
        }

        $normalize = fn ($v) => strtoupper(trim((string) $v));
        $serialsOf = fn ($raw) => array_values(array_filter(
            array_map($normalize, preg_split('/[\n,;\r]+/', (string) $raw)),
            'strlen'
        ));
        $poSerialsOf = function (PurchaseOrderItem $poItem) use ($normalize) {
            $decoded = json_decode((string) $poItem->serial_number, true);
            $serials = is_array($decoded) ? array_map($normalize, $decoded) : [];
            // PO license thường ghi S/N ngay trong tên dòng (VD: "FGT80FTK21034117 (Coterm ...)")
            if (preg_match('/^\s*([A-Z0-9]{8,})\b/i', (string) $poItem->product_name, $m)) {
                $serials[] = $normalize($m[1]);
            }
            return array_values(array_filter(array_unique($serials), 'strlen'));
        };
        $newSerials = $newItems->mapWithKeys(fn ($n) => [$n->id => $serialsOf($n->serial_number)]);

        $moved = 0;
        foreach ($oldItems as $old) {
            $poItems = PurchaseOrderItem::where('sale_order_request_item_id', $old->id)->get();
            if ($poItems->isEmpty()) {
                continue;
            }

            $sameProduct = $newItems->filter(function ($new) use ($old, $normalize) {
                if ($old->vendor_id && $new->vendor_id && (int) $old->vendor_id !== (int) $new->vendor_id) {
                    return false;
                }
                if ($old->sale_item_id && $new->sale_item_id && (int) $old->sale_item_id === (int) $new->sale_item_id) {
                    return true;
                }
                if ($old->product_id && $new->product_id && (int) $old->product_id === (int) $new->product_id) {
                    return true;
                }
                return $normalize($old->part_number) !== '' && $normalize($old->part_number) === $normalize($new->part_number);
            });
            $oldSerials = $serialsOf($old->serial_number);

            foreach ($poItems as $poItem) {
                $poSerials = $poSerialsOf($poItem);
                $bySerial = fn ($pool, $serials) => empty($serials) ? null
                    : $pool->first(fn ($new) => !empty(array_intersect($serials, $newSerials[$new->id] ?? [])));

                // S/N của dòng PO là căn cứ chắc nhất (kể cả khi Sales đổi mã sản phẩm)
                $target = $bySerial($sameProduct, $poSerials)
                    ?? $bySerial($newItems, $poSerials)
                    ?? $bySerial($sameProduct, $oldSerials)
                    ?? $sameProduct->first();

                if ($target) {
                    $poItem->sale_order_request_item_id = $target->id;
                    $poItem->saveQuietly();
                    $moved++;
                }
            }
        }

        return $moved;
    }

    /**
     * Hoàn tất thay dòng yêu cầu: chuyển liên kết PO sang dòng mới rồi xóa dòng cũ.
     * Dòng cũ Sales bỏ hẳn nhưng đang nằm trên PO còn hiệu lực thì giữ lại ở trạng thái
     * "đã hủy" (PO giữ SO, PO Team thấy ở mục sản phẩm đã hủy); nếu chỉ còn trên PO đã hủy
     * thì ghi SO thẳng vào PO đó để không hiện N/A.
     */
    public static function finishReplacement(SaleOrderRequest $sor, $oldItems, $newItems): void
    {
        static::carryPurchaseOrderLinks($oldItems, $newItems);

        $deleteIds = [];
        foreach ($oldItems as $old) {
            $poItems = PurchaseOrderItem::with('purchaseOrder')->where('sale_order_request_item_id', $old->id)->get();
            $onActivePo = $poItems->contains(fn ($p) => $p->purchaseOrder && $p->purchaseOrder->status !== 'cancelled');

            if ($onActivePo) {
                $old->forceFill([
                    'is_cancelled' => true,
                    'cancel_reason' => 'Sales đã bỏ dòng này khi chỉnh sửa yêu cầu (đang nằm trên PO)',
                ])->saveQuietly();
                continue;
            }

            if ($sor->sale_id) {
                foreach ($poItems->pluck('purchaseOrder')->filter()->unique('id') as $po) {
                    if (!$po->sale_id) {
                        $po->forceFill(['sale_id' => $sor->sale_id])->saveQuietly();
                    }
                }
            }
            $deleteIds[] = $old->id;
        }

        if ($deleteIds) {
            static::whereIn('id', $deleteIds)->delete();
        }
    }

    public function purchaseOrderItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'sale_order_request_item_id');
    }

    /**
     * Determine if this item is classified as a Project item (Hàng dự án) vs Run-rate item (Hàng run-rate)
     */
    public function isProjectItem(): bool
    {
        // 0. Nếu đơn hàng bán có dự án (project_id hoặc type = 'project')
        $sale = $this->saleOrderRequest?->sale;
        if ($sale && (!empty($sale->project_id) || $sale->type === 'project')) {
            return true;
        }

        // 0b. Phân loại theo deal_item_type của SaleItem nếu có
        $dealItemType = $this->saleItem?->deal_item_type;
        if ($dealItemType === 'project') {
            return true;
        }
        if (in_array($dealItemType, ['runrate_no_cq', 'runrate_with_cq'])) {
            return false;
        }

        $vendorName = strtolower($this->vendor ?? ($this->product?->brand ?? ''));
        $partNumber = strtolower($this->part_number ?? ($this->product?->code ?? ''));
        $isFortinet = str_contains($vendorName, 'fortinet') 
            || str_starts_with($partNumber, 'fa') 
            || str_starts_with($partNumber, 'fg') 
            || str_starts_with($partNumber, 'fw') 
            || str_starts_with($partNumber, 'fc') 
            || str_starts_with($partNumber, 'fad');

        $type = strtoupper(trim((string)($this->type ?? '')));
        $needsCq = (bool)($this->needs_cq ?? false);
        $euInfo = trim((string)($this->eu_name_mst ?? $this->eu_name ?? ''));

        if ($isFortinet) {
            // Fortinet: HW without CQ checkbox & without EU info -> Run-rate
            if ($type === 'HW' && !$needsCq && empty($euInfo)) {
                return false;
            }
            return true;
        } else {
            // Non-Fortinet: empty EU info & needs_cq false -> Run-rate
            if (empty($euInfo) && !$needsCq) {
                return false;
            }
            return true;
        }
    }

    /**
     * Tính tổng số lượng đã đặt hàng từ các PO liên kết (loại trừ các PO đã hủy)
     */
    public function getOrderedQuantityTotalAttribute(): float
    {
        return (float) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->sum('ordered_quantity');
    }

    /**
     * Tính tổng số lượng thực tế đã nhận hàng (loại trừ các PO đã hủy)
     */
    public function getReceivedQuantityTotalAttribute(): float
    {
        return (float) $this->purchaseOrderItems()
            ->whereHas('purchaseOrder', function ($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->sum('received_quantity');
    }

    /**
     * Tính số lượng còn lại cần đặt
     */
    public function getRemainingOrderQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity - $this->ordered_quantity_total);
    }

    /**
     * Alias: Tính số lượng còn lại cần đặt (dùng trong API getPrItems)
     */
    public function getRemainingToOrderAttribute(): float
    {
        return $this->remaining_order_quantity;
    }

    /**
     * Alias relationship cho backward compatibility
     */
    public function orderRequest(): BelongsTo
    {
        return $this->saleOrderRequest();
    }
}

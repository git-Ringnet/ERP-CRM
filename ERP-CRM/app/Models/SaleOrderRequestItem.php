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

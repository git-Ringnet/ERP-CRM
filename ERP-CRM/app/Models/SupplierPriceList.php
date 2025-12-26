<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class SupplierPriceList extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'supplier_id',
        'file_name',
        'effective_date',
        'expiry_date',
        'currency',
        'exchange_rate',
        'price_type',
        'notes',
        'import_log',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'import_log' => 'array',
        'is_active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierPriceListItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForSupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function getPriceTypeLabelAttribute(): string
    {
        return match($this->price_type) {
            'list' => 'Giá niêm yết',
            'partner' => 'Giá đối tác',
            'cost' => 'Giá gốc',
            default => 'Không xác định',
        };
    }

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        
        $today = now();
        if ($this->effective_date && $today->lt($this->effective_date)) return false;
        if ($this->expiry_date && $today->gt($this->expiry_date)) return false;
        
        return true;
    }

    public static function generateCode(int $supplierId): string
    {
        $supplier = Supplier::find($supplierId);
        $prefix = $supplier ? strtoupper(substr($supplier->code ?? $supplier->name, 0, 3)) : 'SPL';
        $date = date('Ymd');
        
        $last = static::where('code', 'like', "{$prefix}-{$date}-%")
            ->orderBy('code', 'desc')
            ->first();

        $number = $last ? intval(substr($last->code, -3)) + 1 : 1;

        return "{$prefix}-{$date}-" . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}

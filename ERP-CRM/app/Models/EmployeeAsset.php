<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAsset extends Model
{
    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'tracking_type',
        'serial_number',
        'quantity_total',
        'quantity_available',
        'brand',
        'purchase_date',
        'purchase_price',
        'warranty_expiry',
        'status',
        'location',
        'description',
        'image',
    ];

    protected $casts = [
        'purchase_date'    => 'date',
        'warranty_expiry'  => 'date',
        'purchase_price'   => 'decimal:2',
        'quantity_total'   => 'integer',
        'quantity_available' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssetAssignment::class)->orderBy('assigned_date', 'desc');
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeAssetAssignment::class)->where('status', 'active');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }
        return $query->where(function ($q) use ($search) {
            $q->where('asset_code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('serial_number', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%");
        });
    }

    public function scopeFilterByCategory(Builder $query, ?string $category): Builder
    {
        return empty($category) ? $query : $query->where('category', $category);
    }

    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        return empty($status) ? $query : $query->where('status', $status);
    }

    public function scopeFilterByTrackingType(Builder $query, ?string $type): Builder
    {
        return empty($type) ? $query : $query->where('tracking_type', $type);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available'   => 'Sẵn sàng',
            'assigned'    => 'Đang cấp phát',
            'maintenance' => 'Đang bảo trì',
            'disposed'    => 'Thanh lý',
            default       => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'available'   => 'success',
            'assigned'    => 'primary',
            'maintenance' => 'warning',
            'disposed'    => 'secondary',
            default       => 'secondary',
        };
    }

    public function getTrackingTypeLabelAttribute(): string
    {
        return $this->tracking_type === 'serial' ? 'Theo mã/serial' : 'Theo số lượng';
    }

    public function getIsSerialAttribute(): bool
    {
        return $this->tracking_type === 'serial';
    }

    // ─── Business Logic ───────────────────────────────────────────────────────

    /**
     * Giảm số lượng còn có sẵn khi cấp phát.
     * Với serial-type, đổi status → assigned.
     */
    public function decrementAvailable(int $qty = 1): void
    {
        if ($this->tracking_type === 'serial') {
            $this->update(['status' => 'assigned']);
        } else {
            $newAvailable = max(0, $this->quantity_available - $qty);
            $newStatus = $newAvailable === 0 ? 'assigned' : 'available';
            $this->update([
                'quantity_available' => $newAvailable,
                'status' => $newStatus,
            ]);
        }
    }

    /**
     * Tăng số lượng còn có sẵn khi thu hồi.
     * Với serial-type, đổi status → available.
     */
    public function incrementAvailable(int $qty = 1): void
    {
        if ($this->tracking_type === 'serial') {
            $this->update(['status' => 'available']);
        } else {
            $newAvailable = min($this->quantity_total, $this->quantity_available + $qty);
            $this->update([
                'quantity_available' => $newAvailable,
                'status' => 'available',
            ]);
        }
    }
}

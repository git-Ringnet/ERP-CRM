<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'area',
        'capacity',
        'manager_id',
        'phone',
        'status',
        'product_type',
        'has_temperature_control',
        'has_security_system',
        'note',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'capacity' => 'integer',
        'has_temperature_control' => 'boolean',
        'has_security_system' => 'boolean',
    ];

    /**
     * Get the manager of the warehouse.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the inventories in this warehouse.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the imports for this warehouse.
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /**
     * Get the exports for this warehouse.
     */
    public function exports(): HasMany
    {
        return $this->hasMany(Export::class);
    }

    /**
     * Get the transfers from this warehouse.
     */
    public function transfersFrom(): HasMany
    {
        return $this->hasMany(Transfer::class, 'from_warehouse_id');
    }

    /**
     * Get the transfers to this warehouse.
     */
    public function transfersTo(): HasMany
    {
        return $this->hasMany(Transfer::class, 'to_warehouse_id');
    }

    /**
     * Scope: Active warehouses only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if warehouse has inventory.
     */
    public function hasInventory(): bool
    {
        return $this->inventories()->exists();
    }

    /**
     * Generate unique warehouse code.
     */
    public static function generateCode(): string
    {
        $prefix = 'WH';
        $lastWarehouse = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastWarehouse ? ((int) substr($lastWarehouse->code, 2)) + 1 : 1;
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get status label in Vietnamese.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Đang hoạt động',
            'maintenance' => 'Đang bảo trì',
            'inactive' => 'Ngừng hoạt động',
            default => $this->status,
        };
    }

    /**
     * Get type label in Vietnamese.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'physical' => 'Kho vật lý',
            'virtual' => 'Kho ảo',
            default => $this->type,
        };
    }
}

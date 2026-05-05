<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\LogsActivity;

class SaleOrderRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code',
        'sale_id',
        'created_by',
        'note',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Hardcoded vendor list for dropdown
     */
    public const VENDORS = [
        'Fortinet',
        'Array Network',
        'Zyxel Network',
        'Qnap',
        'Bitdefender',
        'Secui',
        'CP Plus',
        'Group-IB',
        'Ben Q',
        'TP-Link',
        'Sonicwall',
        'Perle',
        'Norden',
        'Other',
    ];

    /**
     * Hardcoded type list for dropdown
     */
    public const TYPES = [
        'HW',
        'License',
        'DRMA',
        'Demo',
        'Training',
        'Other',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleOrderRequestItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SaleOrderRequestAttachment::class);
    }

    /**
     * Generate unique code: SOR-YYMM-XXXX
     */
    public static function generateCode(): string
    {
        $prefix = 'SOR' . now()->format('ymd');
        $lastCode = self::where('code', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('code');

        if ($lastCode) {
            $number = (int) substr($lastCode, -4) + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}

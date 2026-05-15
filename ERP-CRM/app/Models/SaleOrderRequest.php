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
        'status',
        'rejection_note',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * PR Statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_NEED_INFO = 'need_info';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_SUBMITTED => 'Đã gửi',
            self::STATUS_NEED_INFO => 'Thiếu thông tin',
            self::STATUS_PROCESSING => 'Đang xử lý',
            self::STATUS_COMPLETED => 'Hoàn thành',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatusLabels()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return [
            self::STATUS_DRAFT => 'gray',
            self::STATUS_SUBMITTED => 'blue',
            self::STATUS_NEED_INFO => 'orange',
            self::STATUS_PROCESSING => 'purple',
            self::STATUS_COMPLETED => 'green',
        ][$this->status] ?? 'gray';
    }

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

    /**
     * Kiểm tra và tự động cập nhật trạng thái dựa trên các items
     * Hỗ trợ revert: completed → processing → submitted khi PO bị hủy
     */
    public function checkAndUpdateStatus(): void
    {
        // Cho phép revert từ completed
        if (!in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_PROCESSING, self::STATUS_COMPLETED])) {
            return;
        }

        $items = $this->items()->where('is_cancelled', false)->get();
        $allCompleted = true;
        $anyOrdered = false;

        foreach ($items as $item) {
            $ordered = $item->ordered_quantity_total;
            if ($ordered < $item->quantity) {
                $allCompleted = false;
            }
            if ($ordered > 0) {
                $anyOrdered = true;
            }
        }

        $newStatus = $this->status;
        if ($items->isEmpty()) {
            // Tất cả items bị hủy → revert về submitted
            $newStatus = self::STATUS_SUBMITTED;
        } elseif ($allCompleted) {
            $newStatus = self::STATUS_COMPLETED;
        } elseif ($anyOrdered) {
            $newStatus = self::STATUS_PROCESSING;
        } else {
            // Không có item nào được ordered → revert về submitted
            $newStatus = self::STATUS_SUBMITTED;
        }

        if ($newStatus !== $this->status) {
            $this->status = $newStatus;
            $this->save();
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

use App\Traits\LogsActivity;

class SaleOrderRequest extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'sale_id',
        'source_type',
        'ticket_id',
        'created_by',
        'note',
        'is_license_from_other_distributor',
        'other_distributor_name',
        'sent_at',
        'status',
        'rejection_note',
        'delete_reason',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_license_from_other_distributor' => 'boolean',
    ];

    /**
     * PR Statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_ADMIN = 'pending_admin';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_NEED_INFO = 'need_info';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_DRAFT => 'Bản nháp',
            self::STATUS_PENDING_ADMIN => 'Chờ Admin duyệt',
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
            self::STATUS_PENDING_ADMIN => 'yellow',
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

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function deleteLog(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ActivityLog::class, 'subject_id')
            ->where('subject_type', get_class($this))
            ->where('action', 'deleted')
            ->latest('id');
    }

    /**
     * Generate unique code: SOR-YYMM-XXXX
     */
    public static function generateCode(): string
    {
        $prefix = 'SOR' . now()->format('ymd');
        return $prefix . str_pad(self::getHighestSequence($prefix) + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a request with a code allocated atomically for the current day.
     *
     * The sequence row is locked until the surrounding transaction commits,
     * preventing duplicate codes when requests are submitted concurrently.
     */
    public static function createWithGeneratedCode(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $prefix = 'SOR' . now()->format('ymd');

            DB::table('sale_order_request_sequences')->insertOrIgnore([
                'prefix' => $prefix,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('sale_order_request_sequences')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            $nextNumber = max((int) $sequence->last_number, self::getHighestSequence($prefix)) + 1;

            DB::table('sale_order_request_sequences')
                ->where('prefix', $prefix)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            $attributes['code'] = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            return self::create($attributes);
        });
    }

    private static function getHighestSequence(string $prefix): int
    {
        return self::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->pluck('code')
            ->filter(fn (string $code) => preg_match('/^' . preg_quote($prefix, '/') . '\\d{4}$/', $code))
            ->map(fn (string $code) => (int) substr($code, -4))
            ->max() ?? 0;
    }

    /**
     * Kiểm tra và tự động cập nhật trạng thái dựa trên các items
     * Hỗ trợ revert: completed → processing → submitted khi PO bị hủy
     */
    public function checkAndUpdateStatus(): void
    {
        // Cho phép revert từ completed hoặc need_info (nếu trước đó bị hủy hết)
        if (!in_array($this->status, [self::STATUS_PROCESSING, self::STATUS_COMPLETED, self::STATUS_NEED_INFO])) {
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
            // Tất cả items bị hủy → chuyển về need_info để Sales biết và xử lý
            $newStatus = self::STATUS_NEED_INFO;
            if (empty($this->rejection_note)) {
                $this->rejection_note = 'Tất cả sản phẩm trong yêu cầu đã bị hủy tại bước Gom đơn. Vui lòng kiểm tra và chỉnh sửa lại yêu cầu.';
            }
        } elseif ($allCompleted) {
            $newStatus = self::STATUS_COMPLETED;
        } else {
            $newStatus = self::STATUS_PROCESSING;
            if ($this->status === self::STATUS_NEED_INFO) {
                $this->rejection_note = null;
            }
        }

        if ($newStatus !== $this->status) {
            $this->status = $newStatus;
            $this->save();
        }
    }
}

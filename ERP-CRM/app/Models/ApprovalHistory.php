<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_id',
        'level',
        'level_name',
        'approver_id',
        'approver_name',
        'original_approver_id',
        'delegated_to_id',
        'action',
        'comment',
        'action_at',
    ];

    protected $attributes = [
        'level' => 0,
        'level_name' => '',
        'approver_name' => '',
        'action' => 'pending',
    ];

    protected $casts = [
        'level' => 'integer',
        'action_at' => 'datetime',
        'original_approver_id' => 'integer',
        'delegated_to_id' => 'integer',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function originalApprover()
    {
        return $this->belongsTo(User::class, 'original_approver_id');
    }

    public function delegatedTo()
    {
        return $this->belongsTo(User::class, 'delegated_to_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'skipped' => 'Bỏ qua',
            'delegated' => 'Đã chuyển',
            'submitted' => 'Yêu cầu duyệt',
            'need_revision' => 'Yêu cầu chỉnh sửa',
            'adjusted' => 'Điều chỉnh sau duyệt',
            default => $this->action,
        };
    }

    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'skipped' => 'bg-gray-100 text-gray-500',
            'delegated' => 'bg-blue-100 text-blue-800',
            'submitted' => 'bg-indigo-100 text-indigo-800',
            'need_revision' => 'bg-orange-100 text-orange-800',
            'adjusted' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Ghi nhận lịch sử yêu cầu chỉnh sửa lại P&L từ các bước mua hàng (PR, Gom đơn, PO)
     */
    public static function recordPnlRevision(Sale $sale, ?User $user, string $comment): self
    {
        $lastHistory = static::where('document_type', 'sale_pnl')
            ->where('document_id', $sale->id)
            ->whereIn('action', ['approved', 'need_revision', 'rejected'])
            ->orderByDesc('level')
            ->first();

        $level = $lastHistory ? $lastHistory->level : 1;
        $levelName = $lastHistory ? $lastHistory->level_name : 'Kiểm duyệt P&L';
        $user = $user ?? auth()->user();

        return static::create([
            'document_type' => 'sale_pnl',
            'document_id'   => $sale->id,
            'level'         => $level,
            'level_name'    => $levelName,
            'approver_id'   => $user?->id,
            'approver_name' => $user?->name ?? 'Quản trị viên',
            'action'        => 'need_revision',
            'comment'       => $comment,
            'action_at'     => now(),
        ]);
    }

    public function pnlAttachments()
    {
        return $this->hasMany(PnlApprovalAttachment::class, 'approval_history_id');
    }

    public static function getForDocument(string $type, int $id)
    {
        return static::where('document_type', $type)
            ->where('document_id', $id)
            ->orderBy('level')
            ->orderBy('created_at')
            ->get();
    }
}

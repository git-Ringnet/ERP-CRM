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
            default => 'bg-gray-100 text-gray-800',
        };
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

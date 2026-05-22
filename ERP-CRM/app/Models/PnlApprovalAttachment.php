<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PnlApprovalAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'approval_history_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'note',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function approvalHistory()
    {
        return $this->belongsTo(ApprovalHistory::class, 'approval_history_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get icon class based on file type
     */
    public function getFileIconAttribute(): string
    {
        $ext = strtolower($this->file_extension);
        return match(true) {
            in_array($ext, ['pdf']) => 'fas fa-file-pdf text-red-500',
            in_array($ext, ['doc', 'docx']) => 'fas fa-file-word text-blue-500',
            in_array($ext, ['xls', 'xlsx']) => 'fas fa-file-excel text-green-500',
            in_array($ext, ['ppt', 'pptx']) => 'fas fa-file-powerpoint text-orange-500',
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']) => 'fas fa-file-image text-purple-500',
            in_array($ext, ['zip', 'rar', '7z']) => 'fas fa-file-archive text-yellow-600',
            default => 'fas fa-file text-gray-500',
        };
    }
}

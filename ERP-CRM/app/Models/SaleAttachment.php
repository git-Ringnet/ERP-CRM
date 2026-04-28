<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleAttachment extends Model
{
    protected $fillable = [
        'sale_id',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get human-readable file size
     */
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Get icon class based on file type
     */
    public function getFileIconAttribute(): string
    {
        return match($this->file_extension) {
            'pdf' => 'fas fa-file-pdf text-red-500',
            'doc', 'docx' => 'fas fa-file-word text-blue-500',
            'xls', 'xlsx' => 'fas fa-file-excel text-green-500',
            'ppt', 'pptx' => 'fas fa-file-powerpoint text-orange-500',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'fas fa-file-image text-purple-500',
            'zip', 'rar', '7z' => 'fas fa-file-archive text-yellow-600',
            default => 'fas fa-file text-gray-500',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatabaseBackup extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'backup_password',
        'user_id',
        'size',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

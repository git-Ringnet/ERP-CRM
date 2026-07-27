<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingRequestComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_request_id',
        'user_id',
        'comment',
        'attachment_path',
    ];

    public function request()
    {
        return $this->belongsTo(MarketingRequest::class, 'marketing_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketingTicket extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'marketing_event_id',
        'code',
        'type',
        'status',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $year = date('Y');
        $prefix = 'TKT-' . $year . '-';
        $last = self::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($last) {
            $parts = explode('-', $last->code);
            $num = (int) end($parts);
            $next = $num + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function event()
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requests()
    {
        return $this->hasMany(MarketingRequest::class, 'marketing_ticket_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'internal_collaboration' => 'Internal Collaboration',
            'business_trip'          => 'Business Trip',
            'payment'                => 'Payment',
            'others'                 => 'Others',
            default                  => $this->type,
        };
    }
}

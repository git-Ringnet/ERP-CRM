<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    /**
     * Get setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        
        Cache::forget("setting.{$key}");
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return self::where('group', $group)->pluck('value', 'key')->toArray();
    }

    /**
     * Apply email settings to config
     */
    public static function applyEmailConfig(): void
    {
        $settings = self::getByGroup('email');
        
        if (!empty($settings)) {
            Config::set('mail.mailers.smtp.host', $settings['mail_host'] ?? 'smtp.gmail.com');
            Config::set('mail.mailers.smtp.port', $settings['mail_port'] ?? 587);
            Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?? '');
            Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?? '');
            Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption'] ?? 'tls');
            Config::set('mail.from.address', $settings['mail_from_address'] ?? 'noreply@minierp.com');
            Config::set('mail.from.name', $settings['mail_from_name'] ?? 'Mini ERP');
        }
    }
}

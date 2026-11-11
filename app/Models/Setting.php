<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        // Load every setting in ONE query per request (cached), instead of
        // one database round-trip per key - vital when the database is far
        // from the web server.
        $all = Cache::rememberForever('settings.all', function () {
            return static::pluck('value', 'key')->all();
        });

        return $all[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
        Cache::forget("setting.{$key}");
    }

    public static function setMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::set($key, $value === null ? null : (string) $value);
        }
    }

    /** Digits-only admin WhatsApp number, used for the wa.me fallback link. */
    public static function whatsappNumber(): string
    {
        return preg_replace('/\D+/', '', (string) static::get('admin_whatsapp', ''));
    }

    public static function whatsappReferralLink(): string
    {
        $number = static::whatsappNumber();
        $text = rawurlencode('Hello, I need a referral code to register.');

        return "https://wa.me/{$number}?text={$text}";
    }
}

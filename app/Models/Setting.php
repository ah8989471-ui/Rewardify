<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = Cache::rememberForever("setting.{$key}", function () use ($key) {
            return static::where('key', $key)->value('value');
        });

        return $value ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
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

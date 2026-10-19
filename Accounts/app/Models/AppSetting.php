<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'description'];

    protected $casts = ['value' => 'string'];

    public static function getValue($key, $default = null)
    {
        try {
            $settings = Cache::remember('app_settings', 3600, fn () => self::all()->pluck('value', 'key')->toArray());
        } catch (\Throwable $e) {
            try {
                $settings = self::all()->pluck('value', 'key')->toArray();
            } catch (\Throwable $inner) {
                return $default;
            }
        }

        return $settings[$key] ?? $default;
    }

    public static function setValue($key, $value): void
    {
        if ($key === 'currency_symbol') {
            $value = self::normalizeCurrencySymbol($value);
        }
        self::updateOrCreate(['key' => $key], ['value' => $value === null ? '' : $value]);
        try {
            Cache::forget('app_settings');
        } catch (\Throwable $e) {
        }
    }

    public static function currencyDecimals(): int
    {
        return max(2, min(5, (int) self::getValue('currency_decimals', 2)));
    }

    public static function sanitizedCurrencySymbol(): string
    {
        return self::normalizeCurrencySymbol(self::getValue('currency_symbol', self::defaultCurrencySymbol()));
    }

    public static function normalizeCurrencySymbol(mixed $value): string
    {
        $fallback = self::defaultCurrencySymbol();
        $symbol = trim(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($symbol === '' || ! mb_check_encoding($symbol, 'UTF-8') || mb_strlen(preg_replace('/\s+/', '', $symbol)) > 5) {
            return $fallback;
        }

        return $symbol;
    }

    private static function defaultCurrencySymbol(): string
    {
        return html_entity_decode('&#8377;', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HrSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        try {
            $all = Cache::remember('hr_settings', 3600, fn () => static::pluck('value', 'key')->toArray());
        } catch (\Throwable $e) {
            $all = static::pluck('value', 'key')->toArray();
        }

        return $all[$key] ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('hr_settings');
    }
}

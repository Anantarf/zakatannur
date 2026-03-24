<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    public const KEY_ACTIVE_YEAR = 'active_year';
    public const KEY_PUBLIC_REFRESH_INTERVAL_SECONDS = 'public_refresh_interval_seconds';

    protected $fillable = [
        'key',
        'value',
    ];

    protected static $settingCache = [];

    public static function getString(string $key, ?string $default = null): ?string
    {
        if (isset(self::$settingCache[$key])) {
            return self::$settingCache[$key];
        }

        $row = self::query()->where('key', $key)->first();
        $value = $row ? (string) $row->value : $default;
        
        self::$settingCache[$key] = $value;
        return $value;
    }

    public static function getInt(string $key, ?int $default = null): ?int
    {
        $value = self::getString($key);
        if ($value === null) {
            return $default;
        }

        if (!is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    public static function normalizePublicRefreshIntervalSeconds(?int $value, int $default = 15): int
    {
        if ($value === null) {
            return $default;
        }

        // Keputusan produk: 0 = off; selain itu 10–60 detik.
        if ($value === 0) {
            return 0;
        }

        if ($value >= 10 && $value <= 60) {
            return $value;
        }

        return $default;
    }
}

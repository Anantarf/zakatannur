<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    public const KEY_ACTIVE_YEAR = 'active_year';
    public const KEY_PUBLIC_REFRESH_INTERVAL_SECONDS = 'public_refresh_interval_seconds';

    protected $fillable = [
        'key',
        'value',
    ];

    protected static array $settingCache = [];

    public static function getString(string $key, ?string $default = null): ?string
    {
        if (isset(self::$settingCache[$key])) {
            return self::$settingCache[$key];
        }

        $value = Cache::remember(self::cacheKeyForSetting($key), (int) config('zakat.cache.app_settings_ttl', 3600), function () use ($key, $default) {
            $row = self::query()->where('key', $key)->first();
            return $row ? (string) $row->value : $default;
        });

        self::$settingCache[$key] = $value;
        return $value;
    }

    public static function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget(self::cacheKeyForSetting($key));
            unset(self::$settingCache[$key]);
        } else {
            foreach (array_keys(self::$settingCache) as $k) {
                Cache::forget(self::cacheKeyForSetting($k));
            }
            Cache::forget(self::cacheKeyForSetting(self::KEY_ACTIVE_YEAR));
            Cache::forget(self::cacheKeyForSetting(self::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS));
            self::$settingCache = [];
        }
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

    public static function normalizePublicRefreshIntervalSeconds(?int $value, int $default = null): int
    {
        $default ??= (int) config('zakat.public_refresh.default_seconds', 15);
        $minSeconds = (int) config('zakat.public_refresh.min_seconds', 10);
        $maxSeconds = (int) config('zakat.public_refresh.max_seconds', 60);

        if ($value === null) {
            return $default;
        }

        // Keputusan produk: 0 = off; selain itu 10–60 detik.
        if ($value === 0) {
            return 0;
        }

        if ($value >= $minSeconds && $value <= $maxSeconds) {
            return $value;
        }

        return $default;
    }

    /**
     * Build cache key for app settings
     */
    public static function cacheKeyForSetting(string $key): string
    {
        return 'app_setting_' . $key;
    }

    /**
     * Build cache key for public summary year
     */
    public static function cacheKeyForPublicSummary(int $year): string
    {
        return 'public_summary_year_' . $year;
    }

    /**
     * Build cache key for public home stats (off-season detection)
     */
    public static function cacheKeyForPublicHomeStats(int $year): string
    {
        return 'public_home_stats_' . $year;
    }

    /**
     * Build cache key for dashboard recap
     */
    public static function cacheKeyForDashboardRekap(?string $yearKey, ?string $metodeKey): string
    {
        $yearKey = $yearKey ?? 'all';
        $metodeKey = $metodeKey ?? 'all';
        return 'dashboard_rekap_' . $yearKey . '_' . $metodeKey;
    }

    /**
     * Build cache key for off-season detection
     */
    public static function cacheKeyForOffSeason(int $year): string
    {
        return 'dashboard_offseason_' . $year;
    }
}

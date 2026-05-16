<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Support\RekapBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PublicSummaryService
{
    public function resolveYear(?int $year): int
    {
        $resolvedYear = $year ?: AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        if ($resolvedYear < 2000 || $resolvedYear > 2100) {
            throw new InvalidArgumentException('Parameter year tidak valid.');
        }

        return $resolvedYear;
    }

    public function refreshIntervalSeconds(): int
    {
        $defaultRefresh = (int) config('zakat.public_refresh.default_seconds', 15);

        return AppSetting::normalizePublicRefreshIntervalSeconds(
            AppSetting::getInt(AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS, $defaultRefresh),
            $defaultRefresh
        );
    }

    public function publicSummaryResponse(int $year): array
    {
        $cacheKey = AppSetting::cacheKeyForPublicSummary($year);
        $cacheTtlSeconds = $this->refreshIntervalSeconds();
        $wasAlreadyCached = Cache::has($cacheKey);

        $payload = Cache::remember($cacheKey, $cacheTtlSeconds, function () use ($year) {
            $rekap = RekapBuilder::build($year);

            return [
                'year' => $year,
                'computed_at_wib' => now('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'items' => $rekap['items'],
                'totals' => $rekap['totals'],
                'dailyChartData' => RekapBuilder::buildDailyChartData($year),
            ];
        });

        return [
            'status' => $wasAlreadyCached ? 'CACHED' : 'FRESH',
            'updated_at_wib' => $payload['computed_at_wib'] . ' WIB',
            'data' => $payload,
        ];
    }

    public function homePageData(int $selectedYear): array
    {
        $refreshIntervalSeconds = $this->refreshIntervalSeconds();
        $cacheKey = AppSetting::cacheKeyForPublicHomeStats($selectedYear);

        $stats = Cache::remember($cacheKey, $refreshIntervalSeconds, function () use ($selectedYear) {
            $historicalData = ZakatTransaction::select('tahun_zakat', DB::raw('SUM(nominal_uang) as total_uang'))
                ->valid()
                ->whereNotNull('tahun_zakat')
                ->groupBy('tahun_zakat')
                ->orderBy('tahun_zakat', 'asc')
                ->get();

            return [
                'historicalChartData' => [
                    'labels' => $historicalData->pluck('tahun_zakat')->toArray(),
                    'data' => $historicalData->pluck('total_uang')->toArray(),
                ],
                'dailyChartData' => RekapBuilder::buildDailyChartData($selectedYear),
            ];
        });

        return array_merge([
            'summaryData' => $this->publicSummaryResponse($selectedYear)['data'],
            'refreshIntervalSeconds' => $refreshIntervalSeconds,
        ], $stats);
    }
}

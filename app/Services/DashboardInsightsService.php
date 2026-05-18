<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardInsightsService
{
    public function buildInsights(int $activeYear, int $activeDays): array
    {
        $offSeasonData = $this->offSeasonData($activeYear);
        $offSeason = $offSeasonData['off_season'];
        $lastActiveDate = $offSeasonData['last_date']
            ? Carbon::parse($offSeasonData['last_date'])->timezone(config('zakat.timezone'))
            : null;

        $chartContext = $this->chartContext($activeYear, $activeDays, $offSeason, $lastActiveDate);

        return [
            'offSeason' => $offSeason,
            'lastActiveDate' => $lastActiveDate,
            'chartPeriodLabel' => $chartContext['chartPeriodLabel'],
            'chartData' => $this->chartData($activeYear, $activeDays, $chartContext['chartEnd'], $offSeason, $chartContext['chartCacheKey']),
        ];
    }

    private function offSeasonData(int $activeYear): array
    {
        $cacheKey = AppSetting::cacheKeyForOffSeason($activeYear);

        return Cache::remember($cacheKey, (int) config('zakat.cache.public_home_stats_ttl', 3600), function () use ($activeYear) {
            $purgeDays = (int) config('zakat.retention.purge_days', 30);
            $effectiveTimestamp = SqlDialect::effectiveTimestamp();

            $hasRecentData = ZakatTransaction::valid()
                ->where('tahun_zakat', $activeYear)
                ->whereRaw("{$effectiveTimestamp} >= ?", [now(config('zakat.timezone'))->subDays($purgeDays)->startOfDay()])
                ->exists();

            if ($hasRecentData) {
                return ['off_season' => false, 'last_date' => null];
            }

            return [
                'off_season' => true,
                'last_date' => ZakatTransaction::valid()
                    ->where('tahun_zakat', $activeYear)
                    ->selectRaw('MAX(' . $effectiveTimestamp . ') as last_date')
                    ->value('last_date'),
            ];
        });
    }

    private function chartContext(int $activeYear, int $activeDays, bool $offSeason, ?Carbon $lastActiveDate): array
    {
        if ($offSeason && $lastActiveDate) {
            $chartEnd = $lastActiveDate->copy()->endOfDay();
            $chartStart = $chartEnd->copy()->subDays($activeDays - 1)->startOfDay();

            return [
                'chartEnd' => $chartEnd,
                'chartPeriodLabel' => $chartStart->locale('id')->translatedFormat('d M') . ' - ' . $chartEnd->locale('id')->translatedFormat('d M Y'),
                'chartCacheKey' => "dashboard_chart_historical_{$activeYear}_{$activeDays}_{$chartEnd->toDateString()}",
            ];
        }

        return [
            'chartEnd' => null,
            'chartPeriodLabel' => null,
            'chartCacheKey' => "dashboard_chart_{$activeYear}_{$activeDays}",
        ];
    }

    private function chartData(int $activeYear, int $activeDays, ?Carbon $chartEnd, bool $offSeason, string $cacheKey): array
    {
        $cacheTtl = $offSeason
            ? (int) config('zakat.cache.public_home_stats_ttl', 3600)
            : (int) config('zakat.cache.public_summary_ttl', 300);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($activeYear, $activeDays, $chartEnd) {
            $endBoundary = $chartEnd ?? now(config('zakat.timezone'))->endOfDay();
            $startBoundary = $endBoundary->copy()->subDays($activeDays - 1)->startOfDay();
            $effectiveTimestamp = SqlDialect::effectiveTimestamp();

            $dailyStats = ZakatTransaction::query()
                ->select(
                    DB::raw(SqlDialect::dateExpression($effectiveTimestamp, 'date')),
                    DB::raw('COUNT(DISTINCT no_transaksi) as count')
                )
                ->valid()
                ->where('tahun_zakat', $activeYear)
                ->whereRaw("{$effectiveTimestamp} >= ?", [$startBoundary])
                ->whereRaw("{$effectiveTimestamp} <= ?", [$endBoundary])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            $statsMap = $dailyStats->pluck('count', 'date');
            $labels = [];
            $values = [];

            for ($i = $activeDays - 1; $i >= 0; $i--) {
                $currentDate = $endBoundary->copy()->subDays($i)->startOfDay();
                $dateStr = $currentDate->format('Y-m-d');
                $labels[] = $currentDate->locale('id')->translatedFormat('d M');
                $values[] = (int) ($statsMap[$dateStr] ?? 0);
            }

            return ['labels' => $labels, 'values' => $values];
        });
    }
}

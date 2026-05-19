<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Services\Charts\ChartRangeResolver;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardInsightsService
{
    private ChartRangeResolver $chartRangeResolver;

    public function __construct(ChartRangeResolver $chartRangeResolver)
    {
        $this->chartRangeResolver = $chartRangeResolver;
    }

    public function buildInsights(int $year, int $activeDays): array
    {
        $offSeasonData = $this->offSeasonData($year);
        $offSeason = $offSeasonData['off_season'];
        $lastActiveDate = $offSeasonData['last_date']
            ? Carbon::parse($offSeasonData['last_date'])->timezone(config('zakat.timezone'))
            : null;

        $range = $this->chartRangeResolver->resolveForYear($year);

        return [
            'offSeason' => $offSeason,
            'lastActiveDate' => $lastActiveDate,
            'chartPeriodLabel' => $range['label'],
            'chartData' => $this->chartData($year, $range, $offSeason),
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

    private function chartData(int $year, array $range, bool $offSeason): array
    {
        $cacheKey = "dashboard_chart_{$year}_{$range['source']}_{$range['starts_at']}_{$range['ends_at']}";
        $cacheTtl = $offSeason
            ? (int) config('zakat.cache.public_home_stats_ttl', 3600)
            : (int) config('zakat.cache.public_summary_ttl', 300);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($year, $range) {
            $startBoundary = Carbon::parse($range['starts_at'])->startOfDay();
            $endBoundary = Carbon::parse($range['ends_at'])->endOfDay();
            $effectiveTimestamp = SqlDialect::effectiveTimestamp();

            $dailyStats = ZakatTransaction::query()
                ->select(
                    DB::raw(SqlDialect::dateExpression($effectiveTimestamp, 'date')),
                    DB::raw('COUNT(DISTINCT no_transaksi) as count')
                )
                ->valid()
                ->where('tahun_zakat', $year)
                ->whereRaw("{$effectiveTimestamp} >= ?", [$startBoundary])
                ->whereRaw("{$effectiveTimestamp} <= ?", [$endBoundary])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            $statsMap = $dailyStats->pluck('count', 'date');
            $labels = [];
            $values = [];

            for ($currentDate = $startBoundary->copy(); $currentDate->lte($endBoundary); $currentDate->addDay()) {
                $dateStr = $currentDate->format('Y-m-d');
                $labels[] = $currentDate->locale('id')->translatedFormat('d M');
                $values[] = (int) ($statsMap[$dateStr] ?? 0);
            }

            return [
                'labels' => $labels,
                'values' => $values,
                'datasets' => [
                    [
                        'key' => 'transactions',
                        'label' => 'Jumlah Transaksi',
                        'unit' => 'count',
                        'values' => $values,
                        'colorRole' => $range['source'] === 'configured' ? 'emerald' : 'amber',
                    ],
                ],
                'range' => [
                    'starts_at' => $range['starts_at'],
                    'ends_at' => $range['ends_at'],
                    'label' => $range['label'],
                    'source' => $range['source'],
                ],
                'empty_state' => ! collect($values)->contains(fn($value) => $value > 0),
            ];
        });
    }
}

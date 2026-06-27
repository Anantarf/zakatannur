<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Services\Charts\ChartRangeResolver;
use App\Services\Transactions\TransactionReviewAssistantService;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardInsightsService
{
    private ChartRangeResolver $chartRangeResolver;
    private TransactionReviewAssistantService $reviewAssistantService;

    public function __construct(
        ChartRangeResolver $chartRangeResolver,
        TransactionReviewAssistantService $reviewAssistantService
    )
    {
        $this->chartRangeResolver = $chartRangeResolver;
        $this->reviewAssistantService = $reviewAssistantService;
    }

    public function buildInsights(?int $year, int $activeDays, ?int $periodId = null, ?string $metode = null): array
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $range = $this->chartRangeResolver->resolveForDashboard();
        $rangeYear = (int) ($range['year'] ?? $activeYear);
        $offSeasonData = $this->offSeasonData($rangeYear);
        $offSeason = $offSeasonData['off_season'];
        $lastActiveDate = $offSeasonData['last_date']
            ? Carbon::parse($offSeasonData['last_date'])->timezone(config('zakat.timezone'))
            : null;
        $workspace = $this->workspaceSnapshot($year ?? $activeYear, $periodId, $metode);

        return [
            'offSeason' => $offSeason,
            'lastActiveDate' => $lastActiveDate,
            'chartPeriodLabel' => $range['label'],
            'chartData' => $this->chartData($rangeYear, $range, $offSeason),
            'workspace' => $workspace,
            'chartRange' => $range,
        ];
    }

    private function workspaceSnapshot(int $year, ?int $periodId, ?string $metode): array
    {
        $effectiveTimestamp = SqlDialect::effectiveTimestamp();
        $hasPeriodColumn = Schema::hasColumn('zakat_transactions', 'zakat_period_id');

        $baseTransactions = ZakatTransaction::query()
            ->valid()
            ->when($year, fn ($query) => $query->where('tahun_zakat', $year))
            ->when($periodId && $hasPeriodColumn, fn ($query) => $query->where('zakat_period_id', $periodId))
            ->when($metode, fn ($query) => $query->where('metode', $metode));

        $todayCount = (clone $baseTransactions)
            ->whereDate(DB::raw($effectiveTimestamp), now(config('zakat.timezone'))->toDateString())
            ->distinct('no_transaksi')
            ->count('no_transaksi');

        $latestTimestamp = (clone $baseTransactions)
            ->selectRaw('MAX(' . $effectiveTimestamp . ') as latest_time')
            ->value('latest_time');

        $warningGroups = $this->reviewAssistantService->warningGroupCount(
            $year,
            $periodId && $hasPeriodColumn ? $periodId : null,
            $metode
        );

        return [
            'today_count' => $todayCount,
            'warning_groups' => $warningGroups,
            'latest_transaction_at' => $latestTimestamp
                ? Carbon::parse($latestTimestamp)->timezone(config('zakat.timezone'))
                : null,
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
                ->where(DB::raw($effectiveTimestamp), '>=', now(config('zakat.timezone'))->subDays($purgeDays)->startOfDay())
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
        $periodKey = $range['period_id'] ?? 'all';
        $cacheKey = "dashboard_chart_{$year}_period_{$periodKey}_{$range['source']}_{$range['starts_at']}_{$range['ends_at']}";
        $cacheTtl = $offSeason
            ? (int) config('zakat.cache.public_home_stats_ttl', 3600)
            : (int) config('zakat.cache.public_summary_ttl', 300);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($year, $range) {
            $startBoundary = Carbon::parse($range['starts_at'])->startOfDay();
            $endBoundary = Carbon::parse($range['ends_at'])->endOfDay();
            $effectiveTimestamp = SqlDialect::effectiveTimestamp();
            $periodId = $range['period_id'] ?? null;

            $dailyStats = ZakatTransaction::query()
                ->select(
                    DB::raw(SqlDialect::dateExpression($effectiveTimestamp, 'date')),
                    DB::raw('COUNT(DISTINCT no_transaksi) as count')
                )
                ->valid()
                ->forPeriodOrYear($periodId, $year)
                ->where(DB::raw($effectiveTimestamp), '>=', $startBoundary)
                ->where(DB::raw($effectiveTimestamp), '<=', $endBoundary)
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

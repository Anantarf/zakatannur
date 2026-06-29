<?php

namespace App\Services\Charts;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\ZakatPeriod;
use App\Models\ZakatTransaction;
use App\Services\Periods\ZakatPeriodResolver;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ChartRangeResolver
{
    public const DASHBOARD_MODE_ACTIVE_PERIOD = 'active_period';
    public const DASHBOARD_MODE_MANUAL_PERIOD = 'manual_period';
    public const DASHBOARD_MODE_LAST_COMPLETED_PERIOD = 'last_completed_period';

    public function __construct(private ZakatPeriodResolver $periodResolver)
    {
    }

    public function resolveForYear(int $year): array
    {
        $period = $this->periodResolver->activeForYear($year);
        $annual = AnnualSetting::query()->where('year', $year)->first();
        $bufferDays = max(0, (int) ($period?->chart_fallback_buffer_days ?? $annual?->chart_fallback_buffer_days ?? 2));

        $configuredStart = ($period?->chart_starts_at ?? $annual?->chart_starts_at)
            ? Carbon::parse($period?->chart_starts_at ?? $annual->chart_starts_at)->startOfDay()
            : null;
        $configuredEnd = ($period?->chart_ends_at ?? $annual?->chart_ends_at)
            ? Carbon::parse($period?->chart_ends_at ?? $annual->chart_ends_at)->endOfDay()
            : null;

        if ($configuredStart && $configuredEnd) {
            return $this->payload($year, $configuredStart, $configuredEnd, 'configured', false, $period);
        }

        $bounds = $this->transactionBounds($year, $period);
        $hasTransactions = $bounds['first_date'] !== null && $bounds['last_date'] !== null;

        $start = $configuredStart
            ?? ($hasTransactions
                ? Carbon::parse($bounds['first_date'])->subDays($bufferDays)->startOfDay()
                : Carbon::create($year, 1, 1)->startOfDay());

        $end = $configuredEnd
            ?? ($hasTransactions
                ? Carbon::parse($bounds['last_date'])->addDays($bufferDays)->endOfDay()
                : $start->copy()->addDays(max(6, $bufferDays * 2))->endOfDay());

        return $this->payload($year, $start, $end, $hasTransactions ? 'transactions' : 'empty_fallback', ! $hasTransactions, $period);
    }

    public function resolveForDashboard(?int $requestedYear = null, ?int $requestedPeriodId = null): array
    {
        if ($requestedPeriodId !== null) {
            $period = ZakatPeriod::query()->find($requestedPeriodId);
            if ($period) {
                return $this->resolveForPeriod($period, 'requested_period');
            }
        }

        if ($requestedYear !== null) {
            return $this->resolveForYear($requestedYear);
        }

        $mode = AppSetting::getString(self::dashboardModeSettingKey(), self::DASHBOARD_MODE_ACTIVE_PERIOD)
            ?? self::DASHBOARD_MODE_ACTIVE_PERIOD;
        $showOffseasonArchive = AppSetting::getBool(AppSetting::KEY_DASHBOARD_CHART_SHOW_OFFSEASON_ARCHIVE, true);

        $resolved = match ($mode) {
            self::DASHBOARD_MODE_MANUAL_PERIOD => $this->resolveManualDashboardPeriod(),
            self::DASHBOARD_MODE_LAST_COMPLETED_PERIOD => $this->resolveLastCompletedDashboardPeriod(),
            default => $this->resolveActiveDashboardPeriod($showOffseasonArchive),
        };

        if ($resolved !== null) {
            return $resolved;
        }

        if ($mode === self::DASHBOARD_MODE_LAST_COMPLETED_PERIOD) {
            $fallback = $this->resolveActiveDashboardPeriod(true);
            if ($fallback !== null) {
                $fallback['source'] = 'dashboard_last_completed_period_fallback_active';
                $fallback['fallback_note'] = 'Belum ada periode yang selesai, jadi grafik sementara memakai periode aktif.';

                return $fallback;
            }
        }

        $fallback = $this->resolveForYear(AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year));
        if ($mode === self::DASHBOARD_MODE_LAST_COMPLETED_PERIOD) {
            $fallback['source'] = 'dashboard_last_completed_period_fallback_year';
            $fallback['fallback_note'] = 'Belum ada periode yang selesai, jadi grafik sementara memakai tahun aktif.';
        }

        return $fallback;
    }

    public static function dashboardModes(): array
    {
        return [
            self::DASHBOARD_MODE_ACTIVE_PERIOD => 'Ikuti Periode Aktif',
            self::DASHBOARD_MODE_MANUAL_PERIOD => 'Pilih Periode Manual',
            self::DASHBOARD_MODE_LAST_COMPLETED_PERIOD => 'Periode Terakhir Selesai',
        ];
    }

    public static function dashboardModeSettingKey(): string
    {
        return AppSetting::KEY_DASHBOARD_CHART_MODE;
    }

    private function resolveActiveDashboardPeriod(bool $showOffseasonArchive): ?array
    {
        $activePeriod = $this->periodResolver->active();
        if ($activePeriod) {
            $range = $this->resolveForPeriod($activePeriod, 'dashboard_active_period');
            if ($showOffseasonArchive && $range['empty_state']) {
                return $this->resolveLastCompletedDashboardPeriod() ?? $range;
            }

            return $range;
        }

        return $showOffseasonArchive ? $this->resolveLastCompletedDashboardPeriod() : null;
    }

    private function resolveManualDashboardPeriod(): ?array
    {
        $manualPeriodId = AppSetting::getInt(AppSetting::KEY_DASHBOARD_CHART_PERIOD_ID);
        if (!$manualPeriodId) {
            return null;
        }

        $period = ZakatPeriod::query()->find($manualPeriodId);
        if (!$period) {
            return null;
        }

        return $this->resolveForPeriod($period, 'dashboard_manual_period');
    }

    private function resolveLastCompletedDashboardPeriod(): ?array
    {
        $period = ZakatPeriod::query()
            ->whereNotNull('ends_at')
            ->whereDate('ends_at', '<=', now(config('app.timezone'))->toDateString())
            ->orderByDesc('ends_at')
            ->orderByDesc('id')
            ->first();

        if (!$period) {
            return null;
        }

        return $this->resolveForPeriod($period, 'dashboard_last_completed_period');
    }

    private function resolveForPeriod(ZakatPeriod $period, string $source): array
    {
        $bufferDays = max(0, (int) ($period->chart_fallback_buffer_days ?? 2));
        $dashboardConfiguredStart = AppSetting::getString(AppSetting::KEY_DASHBOARD_CHART_STARTS_AT);
        $dashboardConfiguredEnd = AppSetting::getString(AppSetting::KEY_DASHBOARD_CHART_ENDS_AT);

        $configuredStart = $dashboardConfiguredStart
            ? Carbon::parse($dashboardConfiguredStart)->startOfDay()
            : ($period->chart_starts_at ? Carbon::parse($period->chart_starts_at)->startOfDay() : null);
        $configuredEnd = $dashboardConfiguredEnd
            ? Carbon::parse($dashboardConfiguredEnd)->endOfDay()
            : ($period->chart_ends_at ? Carbon::parse($period->chart_ends_at)->endOfDay() : null);

        if ($configuredStart && $configuredEnd) {
            return $this->payload($period->gregorian_year, $configuredStart, $configuredEnd, $source, false, $period);
        }

        $bounds = $this->transactionBounds($period->gregorian_year, $period);
        $hasTransactions = $bounds['first_date'] !== null && $bounds['last_date'] !== null;

        $start = $configuredStart
            ?? ($hasTransactions
                ? Carbon::parse($bounds['first_date'])->subDays($bufferDays)->startOfDay()
                : ($period->starts_at ? Carbon::parse($period->starts_at)->startOfDay() : Carbon::create($period->gregorian_year, 1, 1)->startOfDay()));

        $end = $configuredEnd
            ?? ($hasTransactions
                ? Carbon::parse($bounds['last_date'])->addDays($bufferDays)->endOfDay()
                : ($period->ends_at
                    ? Carbon::parse($period->ends_at)->endOfDay()
                    : $start->copy()->addDays(max(6, $bufferDays * 2))->endOfDay()));

        return $this->payload($period->gregorian_year, $start, $end, $hasTransactions ? $source : 'dashboard_empty_fallback', ! $hasTransactions, $period);
    }

    private function transactionBounds(int $year, ?ZakatPeriod $period = null): array
    {
        $cacheKey = 'chart:bounds:' . $year . ':' . ($period?->id ?? 'all');

        return Cache::remember($cacheKey, 3600, function () use ($year, $period) {
            $effectiveTimestamp = SqlDialect::effectiveTimestamp();

            $row = ZakatTransaction::query()
                ->valid()
                ->where('tahun_zakat', $year)
                ->when($period, fn ($query) => $query->where(function ($scope) use ($period) {
                    $scope->where('zakat_period_id', $period->id)
                        ->orWhereNull('zakat_period_id');
                }))
                ->selectRaw('MIN(' . $effectiveTimestamp . ') as first_date')
                ->selectRaw('MAX(' . $effectiveTimestamp . ') as last_date')
                ->first();

            return [
                'first_date' => $row?->first_date,
                'last_date' => $row?->last_date,
            ];
        });
    }

    private function payload(int $year, Carbon $start, Carbon $end, string $source, bool $empty, ?ZakatPeriod $period = null): array
    {
        if ($end->lt($start)) {
            $end = $start->copy()->endOfDay();
        }

        return [
            'year' => $year,
            'period_id' => $period?->id,
            'period_label' => $period?->display_label,
            'starts_at' => $start->toDateString(),
            'ends_at' => $end->toDateString(),
            'start' => $start,
            'end' => $end,
            'source' => $source,
            'empty_state' => $empty,
            'label' => $start->locale('id')->translatedFormat('d M') . ' - ' . $end->locale('id')->translatedFormat('d M Y'),
        ];
    }
}

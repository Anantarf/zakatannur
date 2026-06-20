<?php

namespace App\Services\Periods;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\ZakatPeriod;
use App\Services\Charts\ChartRangeResolver;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PeriodSettingsService
{
    public function __construct(private ZakatPeriodResolver $periodResolver) {}

    public function applySettings(array $data, int $activeYear, Request $request): void
    {
        $periodLabel = trim((string) ($data['period_label'] ?? '')) ?: 'Ramadan ' . $activeYear;
        $hijriYear = $data['hijri_year'] ?? null;
        $hijriMonth = isset($data['hijri_month'])
            ? (int) $data['hijri_month']
            : (int) ($this->periodResolver->ensureForYear($activeYear)->hijri_month ?? 9);

        $periodStartsAt = $data['period_starts_at'] ?? null;
        $periodEndsAt = $data['period_ends_at'] ?? null;
        $defaultFitrah = (int) $data['default_fitrah_cash_per_jiwa'];
        $defaultFitrahBeras = (float) $data['default_fitrah_beras_per_jiwa'];
        $defaultFidyah = (int) $data['default_fidyah_per_hari'];
        $defaultFidyahBeras = (float) $data['default_fidyah_beras_per_hari'];
        $chartStartsAt = $data['chart_starts_at'] ?? null;
        $chartEndsAt = $data['chart_ends_at'] ?? null;
        $chartFallbackBufferDays = (int) $data['chart_fallback_buffer_days'];
        $publicRefreshIntervalSeconds = (int) $data['public_refresh_interval_seconds'];
        $dashboardChartMode = (string) ($data['dashboard_chart_mode'] ?? ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD);
        $dashboardChartPeriodId = isset($data['dashboard_chart_period_id']) ? (int) $data['dashboard_chart_period_id'] : null;
        $dashboardChartStartsAt = $data['dashboard_chart_starts_at'] ?? null;
        $dashboardChartEndsAt = $data['dashboard_chart_ends_at'] ?? null;
        $dashboardChartShowOffseasonArchive = (bool) ($data['dashboard_chart_show_offseason_archive'] ?? false);
        $dashboardChartAutoSwitchOnNewActivePeriod = (bool) ($data['dashboard_chart_auto_switch_on_new_active_period'] ?? false);

        DB::transaction(function () use (
            $activeYear, $periodLabel, $hijriYear, $hijriMonth,
            $periodStartsAt, $periodEndsAt,
            $defaultFitrah, $defaultFitrahBeras, $defaultFidyah, $defaultFidyahBeras,
            $chartStartsAt, $chartEndsAt, $chartFallbackBufferDays,
            $publicRefreshIntervalSeconds,
            $dashboardChartMode, $dashboardChartPeriodId,
            $dashboardChartStartsAt, $dashboardChartEndsAt,
            $dashboardChartShowOffseasonArchive, $dashboardChartAutoSwitchOnNewActivePeriod
        ) {
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_ACTIVE_YEAR],
                ['value' => (string) $activeYear]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS],
                ['value' => (string) $publicRefreshIntervalSeconds]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_DASHBOARD_CHART_MODE],
                ['value' => $dashboardChartMode]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_DASHBOARD_CHART_PERIOD_ID],
                ['value' => $dashboardChartPeriodId !== null ? (string) $dashboardChartPeriodId : '']
            );
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_DASHBOARD_CHART_STARTS_AT],
                ['value' => (string) ($dashboardChartStartsAt ?? '')]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_DASHBOARD_CHART_ENDS_AT],
                ['value' => (string) ($dashboardChartEndsAt ?? '')]
            );
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_DASHBOARD_CHART_SHOW_OFFSEASON_ARCHIVE],
                ['value' => $dashboardChartShowOffseasonArchive ? '1' : '0']
            );
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_DASHBOARD_CHART_AUTO_SWITCH_ON_NEW_ACTIVE_PERIOD],
                ['value' => $dashboardChartAutoSwitchOnNewActivePeriod ? '1' : '0']
            );

            AnnualSetting::query()->updateOrCreate(
                ['year' => $activeYear],
                [
                    'default_fitrah_cash_per_jiwa' => $defaultFitrah,
                    'default_fitrah_beras_per_jiwa' => $defaultFitrahBeras,
                    'default_fidyah_per_hari' => $defaultFidyah,
                    'default_fidyah_beras_per_hari' => $defaultFidyahBeras,
                    'chart_starts_at' => $chartStartsAt,
                    'chart_ends_at' => $chartEndsAt,
                    'chart_fallback_buffer_days' => $chartFallbackBufferDays,
                ]
            );

            $period = $this->periodResolver->ensureForYear($activeYear);
            $period->update([
                'label' => $periodLabel,
                'hijri_year' => $hijriYear !== null ? (int) $hijriYear : null,
                'hijri_month' => $hijriMonth !== null ? (int) $hijriMonth : null,
                'starts_at' => $periodStartsAt,
                'ends_at' => $periodEndsAt,
                'default_fitrah_cash_per_jiwa' => $defaultFitrah,
                'default_fitrah_beras_per_jiwa' => $defaultFitrahBeras,
                'default_fidyah_per_hari' => $defaultFidyah,
                'default_fidyah_beras_per_hari' => $defaultFidyahBeras,
                'chart_starts_at' => $chartStartsAt,
                'chart_ends_at' => $chartEndsAt,
                'chart_fallback_buffer_days' => $chartFallbackBufferDays,
            ]);

            $this->periodResolver->activate($period);
        });

        AppSetting::clearCache();
        Cache::forget(AppSetting::cacheKeyForPublicSummary($activeYear));
        Cache::forget(AppSetting::cacheKeyForPublicHomeStats($activeYear));

        Audit::log($request, 'settings.period.updated', null, [
            'active_year' => $activeYear,
            'period_label' => $periodLabel,
            'hijri_year' => $hijriYear,
            'hijri_month' => $hijriMonth,
            'period_starts_at' => $periodStartsAt,
            'period_ends_at' => $periodEndsAt,
            'default_fitrah_cash_per_jiwa' => $defaultFitrah,
            'default_fitrah_beras_per_jiwa' => $defaultFitrahBeras,
            'default_fidyah_per_hari' => $defaultFidyah,
            'default_fidyah_beras_per_hari' => $defaultFidyahBeras,
            'chart_starts_at' => $chartStartsAt,
            'chart_ends_at' => $chartEndsAt,
            'chart_fallback_buffer_days' => $chartFallbackBufferDays,
            'public_refresh_interval_seconds' => $publicRefreshIntervalSeconds,
            'dashboard_chart_mode' => $dashboardChartMode,
            'dashboard_chart_period_id' => $dashboardChartPeriodId,
            'dashboard_chart_starts_at' => $dashboardChartStartsAt,
            'dashboard_chart_ends_at' => $dashboardChartEndsAt,
            'dashboard_chart_show_offseason_archive' => $dashboardChartShowOffseasonArchive,
            'dashboard_chart_auto_switch_on_new_active_period' => $dashboardChartAutoSwitchOnNewActivePeriod,
        ]);
    }

    public function startNewPeriod(int $activeYear, int $newYear, Request $request): ZakatPeriod
    {
        $newPeriod = null;

        DB::transaction(function () use ($activeYear, $newYear, &$newPeriod) {
            $currentAnnual = AnnualSetting::query()->firstOrCreate(
                ['year' => $activeYear],
                [
                    'default_fitrah_cash_per_jiwa' => (int) config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000),
                    'default_fitrah_beras_per_jiwa' => (float) config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.50),
                    'default_fidyah_per_hari' => (int) config('zakat.annual_defaults.fidyah_per_hari', 30000),
                    'default_fidyah_beras_per_hari' => (float) config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75),
                ]
            );

            AnnualSetting::query()->firstOrCreate(
                ['year' => $newYear],
                [
                    'default_fitrah_cash_per_jiwa' => (int) $currentAnnual->default_fitrah_cash_per_jiwa,
                    'default_fitrah_beras_per_jiwa' => (float) $currentAnnual->default_fitrah_beras_per_jiwa,
                    'default_fidyah_per_hari' => (int) $currentAnnual->default_fidyah_per_hari,
                    'default_fidyah_beras_per_hari' => (float) $currentAnnual->default_fidyah_beras_per_hari,
                    'chart_fallback_buffer_days' => (int) ($currentAnnual->chart_fallback_buffer_days ?? 2),
                ]
            );

            $sourcePeriod = $this->periodResolver->activeForYear($activeYear);
            $newPeriod = $this->periodResolver->createNextForYear($newYear, $sourcePeriod);
            $this->periodResolver->activate($newPeriod);

            if (AppSetting::getBool(AppSetting::KEY_DASHBOARD_CHART_AUTO_SWITCH_ON_NEW_ACTIVE_PERIOD, false)) {
                AppSetting::query()->updateOrCreate(
                    ['key' => AppSetting::KEY_DASHBOARD_CHART_MODE],
                    ['value' => ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD]
                );
                AppSetting::query()->updateOrCreate(
                    ['key' => AppSetting::KEY_DASHBOARD_CHART_PERIOD_ID],
                    ['value' => '']
                );
                AppSetting::query()->updateOrCreate(
                    ['key' => AppSetting::KEY_DASHBOARD_CHART_STARTS_AT],
                    ['value' => '']
                );
                AppSetting::query()->updateOrCreate(
                    ['key' => AppSetting::KEY_DASHBOARD_CHART_ENDS_AT],
                    ['value' => '']
                );
            }
        });

        AppSetting::clearCache();

        Audit::log($request, 'period.start_new', null, [
            'from_year' => $activeYear,
            'to_year' => $newYear,
            'to_period_id' => $newPeriod?->id,
        ]);

        return $newPeriod;
    }
}

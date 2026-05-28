<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Services\Charts\ChartRangeResolver;
use App\Services\Periods\ZakatPeriodResolver;
use App\Support\Audit;
use App\Support\ViewOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PeriodSettingsController extends Controller
{
    public function edit(ChartRangeResolver $chartRangeResolver, ZakatPeriodResolver $periodResolver)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        AnnualSetting::query()->firstOrCreate(
            ['year' => $activeYear],
            [
                'default_fitrah_cash_per_jiwa' => (int) config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000),
                'default_fitrah_beras_per_jiwa' => (float) config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.50),
                'default_fidyah_per_hari' => (int) config('zakat.annual_defaults.fidyah_per_hari', 30000),
                'default_fidyah_beras_per_hari' => (float) config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75),
            ]
        );
        $period = $periodResolver->ensureForYear($activeYear);

        $publicRefreshIntervalSecondsRaw = AppSetting::getInt(AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS, 15);
        $publicRefreshIntervalSeconds = AppSetting::normalizePublicRefreshIntervalSeconds($publicRefreshIntervalSecondsRaw, 15);
        $dashboardChartMode = AppSetting::getString(AppSetting::KEY_DASHBOARD_CHART_MODE, ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD)
            ?? ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD;

        return view('internal.settings.period', [
            'years' => ViewOptions::years($activeYear),
            'activeYear' => $activeYear,
            'activePeriod' => $period,
            'chartRange' => $chartRangeResolver->resolveForYear($activeYear),
            'defaultFitrahCashPerJiwa' => (int) $period->default_fitrah_cash_per_jiwa,
            'defaultFitrahBerasPerJiwa' => (float) $period->default_fitrah_beras_per_jiwa,
            'defaultFidyahPerHari' => (int) $period->default_fidyah_per_hari,
            'defaultFidyahBerasPerHari' => (float) $period->default_fidyah_beras_per_hari,
            'chartStartsAt' => optional($period->chart_starts_at)->toDateString(),
            'chartEndsAt' => optional($period->chart_ends_at)->toDateString(),
            'chartFallbackBufferDays' => (int) ($period->chart_fallback_buffer_days ?? 2),
            'publicRefreshIntervalSeconds' => $publicRefreshIntervalSeconds,
            'dashboardChartRange' => $chartRangeResolver->resolveForDashboard(),
            'dashboardChartModes' => ChartRangeResolver::dashboardModes(),
            'dashboardChartMode' => $dashboardChartMode,
            'dashboardChartPeriodId' => AppSetting::getInt(AppSetting::KEY_DASHBOARD_CHART_PERIOD_ID),
            'dashboardChartStartsAt' => AppSetting::getString(AppSetting::KEY_DASHBOARD_CHART_STARTS_AT),
            'dashboardChartEndsAt' => AppSetting::getString(AppSetting::KEY_DASHBOARD_CHART_ENDS_AT),
            'dashboardChartShowOffseasonArchive' => AppSetting::getBool(AppSetting::KEY_DASHBOARD_CHART_SHOW_OFFSEASON_ARCHIVE, true),
            'dashboardChartAutoSwitchOnNewActivePeriod' => AppSetting::getBool(AppSetting::KEY_DASHBOARD_CHART_AUTO_SWITCH_ON_NEW_ACTIVE_PERIOD, false),
            'dashboardChartPeriods' => ViewOptions::periods(),
        ]);
    }

    public function update(Request $request, ZakatPeriodResolver $periodResolver)
    {
        $currentActiveYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $publicRefreshMinSeconds = (int) config('zakat.public_refresh.min_seconds', 10);
        $publicRefreshMaxSeconds = (int) config('zakat.public_refresh.max_seconds', 60);
        $publicRefreshFormMaxSeconds = (int) config('zakat.public_refresh.form_max_seconds', 600);

        $validator = Validator::make($request->all(), [
            'active_year' => ['required', 'integer', 'min:' . (int) config('zakat.year_bounds.min', 2000), 'max:' . (int) config('zakat.year_bounds.max', 2100)],
            'period_label' => ['nullable', 'string', 'max:80'],
            'hijri_year' => ['nullable', 'integer', 'min:1300', 'max:1600'],
            'hijri_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'period_starts_at' => ['nullable', 'date'],
            'period_ends_at' => ['nullable', 'date'],
            'default_fitrah_cash_per_jiwa' => ['required', 'integer', 'min:0', 'max:100000000'],
            'default_fitrah_beras_per_jiwa' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_fidyah_per_hari' => ['required', 'integer', 'min:0', 'max:100000000'],
            'default_fidyah_beras_per_hari' => ['required', 'numeric', 'min:0', 'max:100'],
            'chart_starts_at' => ['nullable', 'date'],
            'chart_ends_at' => ['nullable', 'date'],
            'chart_fallback_buffer_days' => ['required', 'integer', 'min:0', 'max:14'],
            'public_refresh_interval_seconds' => ['required', 'integer', 'min:0', 'max:' . $publicRefreshFormMaxSeconds],
            'dashboard_chart_mode' => ['nullable', 'string'],
            'dashboard_chart_period_id' => ['nullable', 'integer', 'exists:zakat_periods,id'],
            'dashboard_chart_starts_at' => ['nullable', 'date'],
            'dashboard_chart_ends_at' => ['nullable', 'date'],
            'dashboard_chart_show_offseason_archive' => ['nullable', 'boolean'],
            'dashboard_chart_auto_switch_on_new_active_period' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($currentActiveYear, $publicRefreshMinSeconds, $publicRefreshMaxSeconds) {
            $input = $validator->getData();
            $interval = (int) ($input['public_refresh_interval_seconds'] ?? 15);
            $activeYearInput = (int) ($input['active_year'] ?? 0);
            $dashboardChartMode = (string) ($input['dashboard_chart_mode'] ?? ChartRangeResolver::DASHBOARD_MODE_ACTIVE_PERIOD);

            if (!array_key_exists($dashboardChartMode, ChartRangeResolver::dashboardModes())) {
                $validator->errors()->add('dashboard_chart_mode', 'Mode grafik dashboard tidak valid.');
            }

            if ($interval !== 0 && ($interval < $publicRefreshMinSeconds || $interval > $publicRefreshMaxSeconds)) {
                $validator->errors()->add('public_refresh_interval_seconds', 'Interval refresh publik harus 0 (mati) atau ' . $publicRefreshMinSeconds . '-' . $publicRefreshMaxSeconds . ' detik.');
            }

            if ($activeYearInput !== $currentActiveYear) {
                $validator->errors()->add('active_year', 'Perubahan Tahun Aktif hanya bisa lewat "Mulai Periode Baru".');
            }

            $chartStartsAt = $input['chart_starts_at'] ?? null;
            $chartEndsAt = $input['chart_ends_at'] ?? null;
            if ($chartStartsAt && $chartEndsAt && $chartEndsAt < $chartStartsAt) {
                $validator->errors()->add('chart_ends_at', 'Selesai Grafik tidak boleh lebih awal dari Mulai Grafik.');
            }

            $dashboardChartStartsAt = $input['dashboard_chart_starts_at'] ?? null;
            $dashboardChartEndsAt = $input['dashboard_chart_ends_at'] ?? null;
            if ($dashboardChartStartsAt && $dashboardChartEndsAt && $dashboardChartEndsAt < $dashboardChartStartsAt) {
                $validator->errors()->add('dashboard_chart_ends_at', 'Selesai Grafik Dashboard tidak boleh lebih awal dari Mulai Grafik Dashboard.');
            }

            if ($dashboardChartMode === ChartRangeResolver::DASHBOARD_MODE_MANUAL_PERIOD && empty($input['dashboard_chart_period_id'])) {
                $validator->errors()->add('dashboard_chart_period_id', 'Pilih periode grafik saat mode manual digunakan.');
            }

            $periodStartsAt = $input['period_starts_at'] ?? null;
            $periodEndsAt = $input['period_ends_at'] ?? null;
            if ($periodStartsAt && $periodEndsAt && $periodEndsAt < $periodStartsAt) {
                $validator->errors()->add('period_ends_at', 'Selesai Periode tidak boleh lebih awal dari Mulai Periode.');
            }
        });

        $data = $validator->validate();

        $activeYear = $currentActiveYear;
        $periodLabel = trim((string) ($data['period_label'] ?? '')) ?: 'Ramadan ' . $activeYear;
        $hijriYear = $data['hijri_year'] ?? null;
        $hijriMonth = isset($data['hijri_month']) ? (int) $data['hijri_month'] : (int) ($periodResolver->ensureForYear($activeYear)->hijri_month ?? 9);
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
        $dashboardChartShowOffseasonArchive = $request->boolean('dashboard_chart_show_offseason_archive');
        $dashboardChartAutoSwitchOnNewActivePeriod = $request->boolean('dashboard_chart_auto_switch_on_new_active_period');

        DB::transaction(function () use ($periodResolver, $activeYear, $periodLabel, $hijriYear, $hijriMonth, $periodStartsAt, $periodEndsAt, $defaultFitrah, $defaultFitrahBeras, $defaultFidyah, $defaultFidyahBeras, $chartStartsAt, $chartEndsAt, $chartFallbackBufferDays, $publicRefreshIntervalSeconds, $dashboardChartMode, $dashboardChartPeriodId, $dashboardChartStartsAt, $dashboardChartEndsAt, $dashboardChartShowOffseasonArchive, $dashboardChartAutoSwitchOnNewActivePeriod) {
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

            $period = $periodResolver->ensureForYear($activeYear);
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

            $periodResolver->activate($period);
        });

        AppSetting::clearCache();
        \Illuminate\Support\Facades\Cache::forget(AppSetting::cacheKeyForPublicSummary($activeYear));
        \Illuminate\Support\Facades\Cache::forget(AppSetting::cacheKeyForPublicHomeStats($activeYear));

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

        return redirect()->route('internal.settings.period.edit')->with('status', 'Pengaturan periode tersimpan.');
    }

    public function startNewPeriod(Request $request, ZakatPeriodResolver $periodResolver)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $validator = Validator::make($request->all(), [
            'new_year' => ['required', 'integer', 'min:' . (int) config('zakat.year_bounds.min', 2000), 'max:' . (int) config('zakat.year_bounds.max', 2100)],
            'backup_confirmed' => ['accepted'],
            'new_year_confirmation' => ['required', 'string'],
        ]);

        $validator->after(function ($validator) use ($activeYear) {
            $input = $validator->getData();
            $newYear = (int) ($input['new_year'] ?? 0);

            if ($newYear < $activeYear) {
                $validator->errors()->add('new_year', 'Tahun baru tidak boleh lebih kecil dari tahun aktif saat ini.');
            }

            if ((string) ($input['new_year_confirmation'] ?? '') !== (string) $newYear) {
                $validator->errors()->add('new_year_confirmation', 'Ketik tahun baru yang sama untuk mengonfirmasi perubahan periode.');
            }
        });

        $data = $validator->validate();
        $newYear = (int) $data['new_year'];

        $newPeriod = null;
        DB::transaction(function () use ($activeYear, $newYear, $periodResolver, &$newPeriod) {
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

            $sourcePeriod = $periodResolver->activeForYear($activeYear);
            $newPeriod = $periodResolver->createNextForYear($newYear, $sourcePeriod);
            $periodResolver->activate($newPeriod);

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

        return redirect()->route('internal.settings.period.edit')->with('status', 'Periode baru dimulai. Periode aktif sekarang: ' . ($newPeriod?->display_label ?? $newYear) . '.');
    }
}

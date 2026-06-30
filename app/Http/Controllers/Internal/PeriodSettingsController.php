<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internal\UpdatePeriodSettingsRequest;
use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Services\Charts\ChartRangeResolver;
use App\Services\Periods\PeriodSettingsService;
use App\Services\Periods\ZakatPeriodResolver;
use App\Support\ViewOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PeriodSettingsController extends Controller
{
    public function edit(ChartRangeResolver $chartRangeResolver, ZakatPeriodResolver $periodResolver, PeriodSettingsService $service)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $service->ensureAnnualSettingsExist($activeYear);
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

    public function update(UpdatePeriodSettingsRequest $request, PeriodSettingsService $service)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $service->applySettings($request->validated(), $activeYear, $request);

        return redirect()->route('internal.settings.period.edit')->with('status', 'Pengaturan periode tersimpan.');
    }

    public function startNewPeriod(Request $request, PeriodSettingsService $service)
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

            if ($newYear <= $activeYear) {
                $validator->errors()->add('new_year', 'Tahun baru harus lebih besar dari tahun aktif saat ini.');
            }

            if ((string) ($input['new_year_confirmation'] ?? '') !== (string) $newYear) {
                $validator->errors()->add('new_year_confirmation', 'Ketik tahun baru yang sama untuk mengonfirmasi perubahan periode.');
            }
        });

        $data = $validator->validate();
        $newPeriod = $service->startNewPeriod($activeYear, (int) $data['new_year'], $request);

        return redirect()->route('internal.settings.period.edit')->with('status', 'Periode baru dimulai. Periode aktif sekarang: ' . ($newPeriod->display_label ?? $data['new_year']) . '.');
    }
}

<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Support\Audit;
use App\Support\ViewOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PeriodSettingsController extends Controller
{
    public function edit()
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $annual = AnnualSetting::query()->firstOrCreate(
            ['year' => $activeYear],
            [
                'default_fitrah_cash_per_jiwa' => 50000,
                'default_fitrah_beras_per_jiwa' => 2.50,
                'default_fidyah_per_hari' => 50000,
                'default_fidyah_beras_per_hari' => 0.75,
            ]
        );

        $publicRefreshIntervalSecondsRaw = AppSetting::getInt(AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS, 15);
        $publicRefreshIntervalSeconds = AppSetting::normalizePublicRefreshIntervalSeconds($publicRefreshIntervalSecondsRaw, 15);

        $years = ViewOptions::years($activeYear);

        return view('internal.settings.period', [
            'years' => $years,
            'activeYear' => $activeYear,
            'defaultFitrahCashPerJiwa' => (int) $annual->default_fitrah_cash_per_jiwa,
            'defaultFitrahBerasPerJiwa' => (float) $annual->default_fitrah_beras_per_jiwa,
            'defaultFidyahPerHari' => (int) $annual->default_fidyah_per_hari,
            'defaultFidyahBerasPerHari' => (float) $annual->default_fidyah_beras_per_hari,
            'publicRefreshIntervalSeconds' => $publicRefreshIntervalSeconds,
        ]);
    }

    public function update(Request $request)
    {
        $currentActiveYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $validator = Validator::make($request->all(), [
            'active_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'default_fitrah_cash_per_jiwa' => ['required', 'integer', 'min:0', 'max:100000000'],
            'default_fitrah_beras_per_jiwa' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_fidyah_per_hari' => ['required', 'integer', 'min:0', 'max:100000000'],
            'default_fidyah_beras_per_hari' => ['required', 'numeric', 'min:0', 'max:100'],
            'public_refresh_interval_seconds' => ['required', 'integer', 'min:0', 'max:600'],
        ]);

        $validator->after(function ($validator) use ($currentActiveYear) {
            $input = $validator->getData();
            $interval = (int) ($input['public_refresh_interval_seconds'] ?? 15);
            $activeYearInput = (int) ($input['active_year'] ?? 0);

            // Keputusan: 0 = off; selain itu 10–60 detik.
            if ($interval !== 0 && ($interval < 10 || $interval > 60)) {
                $validator->errors()->add('public_refresh_interval_seconds', 'Interval refresh publik harus 0 (mati) atau 10–60 detik.');
            }

            // Antisipasi: perubahan Tahun Aktif hanya via flow "Mulai Periode Baru".
            if ($activeYearInput !== $currentActiveYear) {
                $validator->errors()->add('active_year', 'Perubahan Tahun Aktif hanya bisa lewat "Mulai Periode Baru".');
            }
        });

        $data = $validator->validate();

        $activeYear = $currentActiveYear;
        $defaultFitrah = (int) $data['default_fitrah_cash_per_jiwa'];
        $defaultFitrahBeras = (float) $data['default_fitrah_beras_per_jiwa'];
        $defaultFidyah = (int) $data['default_fidyah_per_hari'];
        $defaultFidyahBeras = (float) $data['default_fidyah_beras_per_hari'];
        $publicRefreshIntervalSeconds = (int) $data['public_refresh_interval_seconds'];

        DB::transaction(function () use ($activeYear, $defaultFitrah, $defaultFitrahBeras, $defaultFidyah, $defaultFidyahBeras, $publicRefreshIntervalSeconds) {
            // Persist active year value (unchanged) so the setting always exists.
            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_ACTIVE_YEAR],
                ['value' => (string) $activeYear]
            );

            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS],
                ['value' => (string) $publicRefreshIntervalSeconds]
            );

            AnnualSetting::query()->updateOrCreate(
                ['year' => $activeYear],
                [
                    'default_fitrah_cash_per_jiwa' => $defaultFitrah,
                    'default_fitrah_beras_per_jiwa' => $defaultFitrahBeras,
                    'default_fidyah_per_hari' => $defaultFidyah,
                    'default_fidyah_beras_per_hari' => $defaultFidyahBeras,
                ]
            );
        });

        Audit::log($request, 'settings.period.updated', null, [
            'active_year' => $activeYear,
            'default_fitrah_cash_per_jiwa' => $defaultFitrah,
            'default_fitrah_beras_per_jiwa' => $defaultFitrahBeras,
            'default_fidyah_per_hari' => $defaultFidyah,
            'default_fidyah_beras_per_hari' => $defaultFidyahBeras,
            'public_refresh_interval_seconds' => $publicRefreshIntervalSeconds,
        ]);

        return redirect()->route('internal.settings.period.edit')->with('status', 'Pengaturan periode tersimpan.');
    }

    public function startNewPeriod(Request $request)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $validator = Validator::make($request->all(), [
            'new_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'backup_confirmed' => ['accepted'],
        ]);

        $validator->after(function ($validator) use ($activeYear) {
            $input = $validator->getData();
            $newYear = (int) ($input['new_year'] ?? 0);

            if ($newYear <= $activeYear) {
                $validator->errors()->add('new_year', 'Tahun baru harus lebih besar dari tahun aktif saat ini.');
            }
        });

        $data = $validator->validate();

        $newYear = (int) $data['new_year'];

        DB::transaction(function () use ($activeYear, $newYear) {
            $currentAnnual = AnnualSetting::query()->firstOrCreate(
                ['year' => $activeYear],
                [
                    'default_fitrah_cash_per_jiwa' => 50000,
                    'default_fitrah_beras_per_jiwa' => 2.50,
                    'default_fidyah_per_hari' => 50000,
                    'default_fidyah_beras_per_hari' => 0.75,
                ]
            );

            AnnualSetting::query()->firstOrCreate(
                ['year' => $newYear],
                [
                    'default_fitrah_cash_per_jiwa' => (int) $currentAnnual->default_fitrah_cash_per_jiwa,
                    'default_fitrah_beras_per_jiwa' => (float) $currentAnnual->default_fitrah_beras_per_jiwa,
                    'default_fidyah_per_hari' => (int) $currentAnnual->default_fidyah_per_hari,
                    'default_fidyah_beras_per_hari' => (float) $currentAnnual->default_fidyah_beras_per_hari,
                ]
            );

            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_ACTIVE_YEAR],
                ['value' => (string) $newYear]
            );
        });

        Audit::log($request, 'period.start_new', null, [
            'from_year' => $activeYear,
            'to_year' => $newYear,
        ]);

        return redirect()->route('internal.settings.period.edit')->with('status', 'Periode baru dimulai. Tahun aktif sekarang: ' . $newYear . '.');
    }
}

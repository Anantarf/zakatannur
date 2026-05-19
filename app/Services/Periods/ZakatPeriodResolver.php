<?php

namespace App\Services\Periods;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\ZakatPeriod;
use Illuminate\Support\Facades\DB;

class ZakatPeriodResolver
{
    public function active(): ?ZakatPeriod
    {
        $activePeriodId = AppSetting::getInt(AppSetting::KEY_ACTIVE_ZAKAT_PERIOD_ID);

        if ($activePeriodId) {
            $period = ZakatPeriod::query()->find($activePeriodId);
            if ($period) {
                return $period;
            }
        }

        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        return $this->activeForYear($activeYear);
    }

    public function activeForYear(int $gregorianYear): ?ZakatPeriod
    {
        return ZakatPeriod::query()
            ->where('gregorian_year', $gregorianYear)
            ->where('is_active', true)
            ->orderByDesc('sequence')
            ->first()
            ?? ZakatPeriod::query()
                ->where('gregorian_year', $gregorianYear)
                ->orderByDesc('sequence')
                ->first();
    }

    public function ensureForYear(int $gregorianYear): ZakatPeriod
    {
        $existing = $this->activeForYear($gregorianYear);
        if ($existing) {
            return $existing;
        }

        $annual = AnnualSetting::query()->firstOrCreate(
            ['year' => $gregorianYear],
            [
                'default_fitrah_cash_per_jiwa' => (int) config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000),
                'default_fitrah_beras_per_jiwa' => (float) config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.50),
                'default_fidyah_per_hari' => (int) config('zakat.annual_defaults.fidyah_per_hari', 30000),
                'default_fidyah_beras_per_hari' => (float) config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75),
            ]
        );

        return ZakatPeriod::query()->create([
            'code' => $this->code($gregorianYear, 1),
            'label' => 'Ramadan ' . $gregorianYear,
            'gregorian_year' => $gregorianYear,
            'hijri_month' => 9,
            'sequence' => 1,
            'default_fitrah_cash_per_jiwa' => (int) $annual->default_fitrah_cash_per_jiwa,
            'default_fitrah_beras_per_jiwa' => (float) $annual->default_fitrah_beras_per_jiwa,
            'default_fidyah_per_hari' => (int) $annual->default_fidyah_per_hari,
            'default_fidyah_beras_per_hari' => (float) $annual->default_fidyah_beras_per_hari,
            'chart_starts_at' => $annual->chart_starts_at,
            'chart_ends_at' => $annual->chart_ends_at,
            'chart_fallback_buffer_days' => (int) ($annual->chart_fallback_buffer_days ?? 2),
        ]);
    }

    public function activate(ZakatPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            ZakatPeriod::query()->whereKeyNot($period->id)->update(['is_active' => false]);
            $period->forceFill(['is_active' => true])->save();

            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_ACTIVE_YEAR],
                ['value' => (string) $period->gregorian_year]
            );

            AppSetting::query()->updateOrCreate(
                ['key' => AppSetting::KEY_ACTIVE_ZAKAT_PERIOD_ID],
                ['value' => (string) $period->id]
            );
        });

        AppSetting::clearCache();
    }

    public function createNextForYear(int $gregorianYear, ?ZakatPeriod $source = null): ZakatPeriod
    {
        $sequence = ((int) ZakatPeriod::query()
            ->where('gregorian_year', $gregorianYear)
            ->max('sequence')) + 1;

        $source ??= $this->active();

        return ZakatPeriod::query()->create([
            'code' => $this->code($gregorianYear, $sequence),
            'label' => 'Ramadan ' . $gregorianYear . ($sequence > 1 ? ' #' . $sequence : ''),
            'gregorian_year' => $gregorianYear,
            'hijri_year' => null,
            'hijri_month' => 9,
            'sequence' => $sequence,
            'default_fitrah_cash_per_jiwa' => (int) ($source?->default_fitrah_cash_per_jiwa ?? config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000)),
            'default_fitrah_beras_per_jiwa' => (float) ($source?->default_fitrah_beras_per_jiwa ?? config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.50)),
            'default_fidyah_per_hari' => (int) ($source?->default_fidyah_per_hari ?? config('zakat.annual_defaults.fidyah_per_hari', 30000)),
            'default_fidyah_beras_per_hari' => (float) ($source?->default_fidyah_beras_per_hari ?? config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75)),
            'chart_fallback_buffer_days' => (int) ($source?->chart_fallback_buffer_days ?? 2),
        ]);
    }

    private function code(int $gregorianYear, int $sequence): string
    {
        return 'ramadan-' . $gregorianYear . '-' . $sequence;
    }
}

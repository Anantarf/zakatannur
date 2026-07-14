<?php

namespace App\Services\Transactions;

use App\Models\AnnualSetting;
use App\Services\Periods\ZakatPeriodResolver;

class AnnualZakatDefaultsResolver
{
    /** @var array<int, AnnualZakatDefaults> */
    private array $cache = [];

    public function __construct(private ZakatPeriodResolver $periodResolver)
    {
    }

    public function resolve(int $year): AnnualZakatDefaults
    {
        if (isset($this->cache[$year])) {
            return $this->cache[$year];
        }

        $period = $this->periodResolver->activeForYear($year);
        if ($period) {
            return $this->cache[$year] = new AnnualZakatDefaults(
                (int) $period->default_fitrah_cash_per_jiwa,
                (int) $period->default_fidyah_per_hari,
                (float) $period->default_fitrah_beras_per_jiwa,
                (float) $period->default_fidyah_beras_per_hari,
                (int) ($period->nishab_gold_gram ?? 85),
                (int) ($period->gold_price_per_gram ?? 900000),
            );
        }

        $annual = AnnualSetting::query()->where('year', $year)->first();

        return $this->cache[$year] = new AnnualZakatDefaults(
            (int) ($annual?->default_fitrah_cash_per_jiwa ?? config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000)),
            (int) ($annual?->default_fidyah_per_hari ?? config('zakat.annual_defaults.fidyah_per_hari', 30000)),
            (float) ($annual?->default_fitrah_beras_per_jiwa ?? config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.5)),
            (float) ($annual?->default_fidyah_beras_per_hari ?? config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75)),
        );
    }
}

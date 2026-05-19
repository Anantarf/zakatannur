<?php

namespace App\Services\Charts;

use App\Models\AnnualSetting;
use App\Models\ZakatPeriod;
use App\Models\ZakatTransaction;
use App\Services\Periods\ZakatPeriodResolver;
use App\Support\SqlDialect;
use Carbon\Carbon;

class ChartRangeResolver
{
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

    private function transactionBounds(int $year, ?ZakatPeriod $period = null): array
    {
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

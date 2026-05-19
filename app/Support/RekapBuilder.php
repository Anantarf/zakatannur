<?php

namespace App\Support;

use App\Services\Charts\ChartRangeResolver;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class RekapBuilder
{
    /**
     * @return array{
     *   items: array<int, array{
     *     category: string,
     *     jumlah_transaksi: int,
     *     total_uang: int,
     *     total_uang_display: string,
     *     total_beras_kg: float,
     *     total_beras_kg_display: string
     *   }>,
     *   totals: array{
     *     jumlah_transaksi: int,
     *     total_uang: int,
     *     total_uang_display: string,
     *     total_beras_kg: float,
     *     total_beras_kg_display: string
     *   }
     * }
     */
    public static function build(?int $year = null, ?string $metode = null, ?int $periodId = null): array
    {
        $baseQuery = ZakatTransaction::query()
            ->valid();

        $baseQuery->forPeriodOrYear($periodId, $year);

        if ($metode !== null && $metode !== '') {
            $baseQuery->where('metode', $metode);
        }

        $rows = $baseQuery
            ->selectRaw('category as category')
            ->selectRaw('COUNT(*) as transaction_row_count')
            ->selectRaw('SUM(COALESCE(jiwa, 1)) as total_jiwa')
            ->selectRaw('COALESCE(SUM(nominal_uang), 0) as total_uang')
            ->selectRaw('COALESCE(SUM(jumlah_beras_kg), 0) as total_beras_kg')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $items = [];
        $totalCount = 0;
        $totalJiwaAccumulated = 0;
        $grandTotalUang = 0;
        $grandTotalBeras = 0.0;

        foreach (ZakatTransaction::CATEGORIES as $category) {
            /** @var \App\Models\ZakatTransaction|null $row */
            $row = $rows->get($category);

            $totalUang = (int) data_get($row, 'total_uang', 0);
            $totalBerasKg = (float) data_get($row, 'total_beras_kg', 0);
            $totalBerasKgRounded = round($totalBerasKg, 2);
            $transactionCount = (int) data_get($row, 'transaction_row_count', 0);
            $soulsCount = (int) data_get($row, 'total_jiwa', 0);

            $items[] = [
                'category'               => $category,
                'jumlah_transaksi'       => $transactionCount,
                'total_jiwa'             => $soulsCount,
                'total_uang'             => $totalUang,
                'total_uang_display'     => Format::rupiah($totalUang),
                'total_beras_kg'         => $totalBerasKgRounded,
                'total_beras_kg_display' => Format::kg($totalBerasKgRounded),
                'total_display'          => Format::rupiah($totalUang) . ' + ' . Format::kg($totalBerasKgRounded),
            ];

            $totalCount += $transactionCount;
            $totalJiwaAccumulated += $soulsCount;
            $grandTotalUang += $totalUang;
            $grandTotalBeras += $totalBerasKg;
        }

        $grandTotalBerasRounded = round($grandTotalBeras, 2);

        return [
            'items'  => $items,
            'totals' => [
                'jumlah_transaksi'       => $totalCount,
                'total_jiwa'             => $totalJiwaAccumulated,
                'total_uang'             => $grandTotalUang,
                'total_uang_display'     => Format::rupiah($grandTotalUang),
                'total_beras_kg'         => $grandTotalBerasRounded,
                'total_beras_kg_display' => Format::kg($grandTotalBerasRounded),
                'total_display'          => Format::rupiah($grandTotalUang) . ' + ' . Format::kg($grandTotalBerasRounded),
            ],
        ];
    }

    /**
     * Build daily chart data for the configured chart window.
     *
     * @return array{labels: string[], uang: int[], beras: float[], datasets: array<int, array<string, mixed>>, range: array<string, mixed>, empty_state: bool}
     */
    public static function buildDailyChartData(?int $year = null, ?array $range = null, ?int $periodId = null): array
    {
        $year = $year ?? (int) now()->year;
        $range = $range ?? app(ChartRangeResolver::class)->resolveForYear($year);
        $start = Carbon::parse($range['starts_at'])->startOfDay();
        $end = Carbon::parse($range['ends_at'])->endOfDay();
        $effectiveTimestamp = SqlDialect::effectiveTimestamp();

        $rows = ZakatTransaction::query()
            ->valid()
            ->forPeriodOrYear($periodId ?? ($range['period_id'] ?? null), $year)
            ->whereRaw("{$effectiveTimestamp} >= ?", [$start])
            ->whereRaw("{$effectiveTimestamp} <= ?", [$end])
            ->select(
                DB::raw(SqlDialect::dateExpression($effectiveTimestamp, 'day')),
                DB::raw('COALESCE(SUM(nominal_uang), 0) as total_uang'),
                DB::raw('COALESCE(SUM(jumlah_beras_kg), 0) as total_beras')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $uang   = [];
        $beras  = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key      = $day->toDateString(); // 'YYYY-MM-DD'
            $row      = $rows->get($key);
            $labels[] = $day->format('j M');  // '1 Jan'
            $uang[]   = $row ? (int) $row->total_uang   : 0;
            $beras[]  = $row ? (float) $row->total_beras : 0.0;
        }

        return [
            'labels' => $labels,
            'uang' => $uang,
            'beras' => $beras,
            'datasets' => [
                [
                    'key' => 'uang',
                    'label' => 'Uang Zakat',
                    'unit' => 'rupiah',
                    'values' => $uang,
                    'colorRole' => 'emerald',
                ],
                [
                    'key' => 'beras',
                    'label' => 'Beras Zakat',
                    'unit' => 'kg',
                    'values' => $beras,
                    'colorRole' => 'amber',
                ],
            ],
            'range' => [
                'starts_at' => $range['starts_at'],
                'ends_at' => $range['ends_at'],
                'label' => $range['label'],
                'source' => $range['source'],
            ],
            'empty_state' => ! collect($uang)->contains(fn($value) => $value > 0)
                && ! collect($beras)->contains(fn($value) => $value > 0),
        ];
    }
}

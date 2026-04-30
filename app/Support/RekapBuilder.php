<?php

namespace App\Support;

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
    public static function build(?int $year = null, ?string $metode = null): array
    {
        $baseQuery = ZakatTransaction::query()
            ->where('status', ZakatTransaction::STATUS_VALID);

        if ($year !== null) {
            $baseQuery->where('tahun_zakat', $year);
        }

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
     * Build daily chart data for the given year.
     *
     * Returns an array with three parallel arrays keyed by:
     *   - 'labels' : string[]  — e.g. ['1 Jan', '2 Jan', ...]
     *   - 'uang'   : int[]    — daily SUM(nominal_uang)
     *   - 'beras'  : float[]  — daily SUM(jumlah_beras_kg)
     *
     * Every day from Jan 1 to min(today, Dec 31) of $year is included,
     * with 0 for days that have no transactions.
     *
     * @return array{labels: string[], uang: int[], beras: float[]}
     */
    public static function buildDailyChartData(?int $year = null): array
    {
        $year = $year ?? (int) now()->year;

        $rows = ZakatTransaction::query()
            ->where('status', ZakatTransaction::STATUS_VALID)
            ->whereYear('created_at', $year)
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COALESCE(SUM(nominal_uang), 0) as total_uang'),
                DB::raw('COALESCE(SUM(jumlah_beras_kg), 0) as total_beras')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = Carbon::create($year, 12, 31)->endOfDay();
        if ($end->isFuture()) {
            $end = now()->startOfDay();
        }

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

        return compact('labels', 'uang', 'beras');
    }
}

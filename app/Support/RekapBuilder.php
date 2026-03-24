<?php

namespace App\Support;

use App\Models\ZakatTransaction;

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
                'category' => $category,
                'jumlah_transaksi' => $transactionCount,
                'total_jiwa' => $soulsCount,
                'total_uang' => $totalUang,
                'total_uang_display' => Format::rupiah($totalUang),
                'total_beras_kg' => $totalBerasKgRounded,
                'total_beras_kg_display' => Format::kg($totalBerasKgRounded),
                'total_display' => Format::rupiah($totalUang) . ' + ' . Format::kg($totalBerasKgRounded),
            ];

            $totalCount += $transactionCount;
            $totalJiwaAccumulated += $soulsCount;
            $grandTotalUang += $totalUang;
            $grandTotalBeras += $totalBerasKg;
        }

        $grandTotalBerasRounded = round($grandTotalBeras, 2);

        return [
            'items' => $items,
            'totals' => [
                'jumlah_transaksi' => $totalCount,
                'total_jiwa' => $totalJiwaAccumulated,
                'total_uang' => $grandTotalUang,
                'total_uang_display' => Format::rupiah($grandTotalUang),
                'total_beras_kg' => $grandTotalBerasRounded,
                'total_beras_kg_display' => Format::kg($grandTotalBerasRounded),
                'total_display' => Format::rupiah($grandTotalUang) . ' + ' . Format::kg($grandTotalBerasRounded),
            ],
        ];
    }

    public static function buildDailyChartData(): array
    {
        $startDate = \Carbon\Carbon::create(2026, 3, 13, 0, 0, 0, 'Asia/Jakarta');
        $maxVisibleDate = now('Asia/Jakarta');

        $dailyStats = ZakatTransaction::select(
            \Illuminate\Support\Facades\DB::raw('DATE(COALESCE(waktu_terima, created_at)) as date'),
            \Illuminate\Support\Facades\DB::raw('SUM(nominal_uang) as total_uang'),
            \Illuminate\Support\Facades\DB::raw('SUM(jumlah_beras_kg) as total_beras')
        )
        ->where('status', ZakatTransaction::STATUS_VALID)
        ->whereRaw('COALESCE(waktu_terima, created_at) >= ?', [$startDate->format('Y-m-d') . ' 00:00:00'])
        ->whereRaw('COALESCE(waktu_terima, created_at) <= ?', [$maxVisibleDate->format('Y-m-d') . ' 23:59:59'])
        ->groupBy('date')
        ->orderBy('date', 'ASC')
        ->get()
        ->keyBy('date');

        $dailyChartLabels = [];
        $dailyChartUang = [];
        $dailyChartBeras = [];
        $runningUang = 0;
        $runningBeras = 0;

        $current = $startDate->copy();
        $endTimeline = $maxVisibleDate->copy()->addDays(2);

        while ($current->lte($endTimeline)) {
            $dateStr = $current->format('Y-m-d');
            $dailyChartLabels[] = $current->translatedFormat('d M');
            
            if ($current->lte($maxVisibleDate)) {
                $stat = $dailyStats->get($dateStr);
                $todayUang = (int) data_get($stat, 'total_uang', 0);
                $todayBeras = (float) data_get($stat, 'total_beras', 0);

                $runningUang += $todayUang;
                $runningBeras += $todayBeras;

                $dailyChartUang[] = $runningUang;
                $dailyChartBeras[] = $runningBeras;
            } else {
                $dailyChartUang[] = null;
                $dailyChartBeras[] = null;
            }
            
            $current->addDay();
        }

        return [
            'labels' => $dailyChartLabels,
            'uang' => $dailyChartUang,
            'beras' => $dailyChartBeras,
        ];
    }
}

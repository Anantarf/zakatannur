<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Support\RekapBuilder;
use App\Support\RekapFilters;
use App\Support\ViewOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $filters = RekapFilters::fromRequest($request);
        $year = $filters['year'];
        $metode = $filters['metode'];
        $activeDays = $request->integer('days', 14);
        if (!in_array($activeDays, [7, 14, 30])) $activeDays = 14;

        $payload = RekapBuilder::build($year, $metode);

        $latestTransactions = ZakatTransaction::query()
            ->select(
                'no_transaksi',
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(waktu_terima) as waktu_terima'),
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('SUM(nominal_uang) as total_uang'),
                DB::raw('SUM(jumlah_beras_kg) as total_beras'),
                DB::raw('MAX(pembayar_nama) as pembayar_nama'),
                DB::raw('MAX(petugas_id) as petugas_id'),
                DB::raw('MAX(shift) as shift'),
                DB::raw('group_concat(DISTINCT category) as categories_list'),
                DB::raw('group_concat(DISTINCT metode) as methods_list'),
                DB::raw('COUNT(DISTINCT muzakki_id) as muzakki_total'),
                DB::raw('MAX(CASE WHEN metode = "uang" THEN is_transfer ELSE 0 END) as has_transfer')
            )
            ->with(['petugas'])
            ->where('status', ZakatTransaction::STATUS_VALID)
            ->when($year !== null, fn ($q) => $q->where('tahun_zakat', $year))
            ->when($metode !== null && $metode !== '', fn ($q) => $q->where('metode', $metode))
            ->groupBy('no_transaksi')
            ->orderByRaw('COALESCE(MAX(waktu_terima), MAX(created_at)) DESC')
            ->orderByDesc('no_transaksi')
            ->limit(10)
            ->get();

        $dailyStats = ZakatTransaction::query()
            ->select(
                DB::raw('DATE(COALESCE(waktu_terima, created_at)) as date'),
                DB::raw('COUNT(DISTINCT no_transaksi) as count')
            )
            ->where('status', ZakatTransaction::STATUS_VALID)
            ->where('tahun_zakat', $activeYear)
            ->whereRaw('COALESCE(waktu_terima, created_at) >= ?', [now('Asia/Jakarta')->subDays($activeDays)->startOfDay()])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $statsMap = $dailyStats->pluck('count', 'date');
        $now = now('Asia/Jakarta');
        $labels = [];
        $values = [];
        
        for ($i = $activeDays - 1; $i >= 0; $i--) {
            $currentDate = $now->copy()->subDays($i);
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->locale('id')->translatedFormat('d M');
            $values[] = (int) ($statsMap[$dateStr] ?? 0);
        }

        $chartData = [
            'labels' => $labels,
            'values' => $values,
        ];

        $years = ViewOptions::years($activeYear);

        return view('dashboard', [
            'activeYear' => $activeYear,
            'year' => $year,
            'metode' => $metode,
            'years' => $years,
            'methods' => ZakatTransaction::METHODS,
            'payload' => $payload,
            'latestTransactions' => $latestTransactions,
            'chartData' => $chartData,
            'activeDays' => $activeDays,
        ]);
    }
}

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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $filters = RekapFilters::fromRequest($request);
        $year = $filters['year'];
        $metode = $filters['metode'];
        $activeDays = $this->resolveActiveDays($request);

        $yearKey = $year ?? 'all';
        $metodeKey = $metode ?? 'all';
        $payload = Cache::remember(AppSetting::cacheKeyForDashboardRekap($yearKey, $metodeKey), (int) config('zakat.cache.public_summary_ttl', 300), fn() => RekapBuilder::build($year, $metode));

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
            ->valid()
            ->when($year !== null, fn ($q) => $q->where('tahun_zakat', $year))
            ->when($metode !== null && $metode !== '', fn ($q) => $q->where('metode', $metode))
            ->groupBy('no_transaksi')
            ->orderByRaw('COALESCE(MAX(waktu_terima), MAX(created_at)) DESC')
            ->orderByDesc('no_transaksi')
            ->limit(10)
            ->get();

        // Deteksi off-season: tidak ada transaksi dalam 30 hari terakhir
        $offSeasonCacheKey = AppSetting::cacheKeyForOffSeason($activeYear);
        $offSeasonData = Cache::remember($offSeasonCacheKey, (int) config('zakat.cache.public_home_stats_ttl', 3600), function () use ($activeYear) {
            $purgeDays = (int) config('zakat.retention.purge_days', 30);

            $hasRecentData = ZakatTransaction::valid()
                ->where('tahun_zakat', $activeYear)
                ->whereRaw('COALESCE(waktu_terima, created_at) >= ?', [now(config('zakat.timezone'))->subDays($purgeDays)->startOfDay()])
                ->exists();

            if ($hasRecentData) {
                return ['off_season' => false, 'last_date' => null];
            }

            $lastDateRaw = ZakatTransaction::valid()
                ->where('tahun_zakat', $activeYear)
                ->selectRaw('MAX(COALESCE(waktu_terima, created_at)) as last_date')
                ->value('last_date');

            return [
                'off_season' => true,
                'last_date'  => $lastDateRaw,
            ];
        });

        $offSeason = $offSeasonData['off_season'];
        $lastActiveDate = $offSeasonData['last_date']
            ? Carbon::parse($offSeasonData['last_date'])->timezone(config('zakat.timezone'))
            : null;

        // Saat off-season, chart menampilkan rentang terakhir aktif, bukan N hari dari sekarang
        if ($offSeason && $lastActiveDate) {
            $chartEnd   = $lastActiveDate->copy()->endOfDay();
            $chartStart = $chartEnd->copy()->subDays($activeDays - 1)->startOfDay();
            $chartPeriodLabel = $chartStart->locale('id')->translatedFormat('d M') . ' – ' . $chartEnd->locale('id')->translatedFormat('d M Y');
            $chartCacheKey = "dashboard_chart_historical_{$activeYear}_{$activeDays}_{$chartEnd->toDateString()}";
        } else {
            $chartEnd   = null;
            $chartStart = now(config('zakat.timezone'))->subDays($activeDays)->startOfDay();
            $chartPeriodLabel = null;
            $chartCacheKey = "dashboard_chart_{$activeYear}_{$activeDays}";
        }

        // Cache lebih panjang (1 jam) saat off-season karena data tidak berubah
        $chartCacheTtl = $offSeason
            ? (int) config('zakat.cache.public_home_stats_ttl', 3600)
            : (int) config('zakat.cache.public_summary_ttl', 300);

        $chartData = Cache::remember($chartCacheKey, $chartCacheTtl, function () use ($activeYear, $activeDays, $chartEnd) {
            $endBoundary   = $chartEnd ?? now(config('zakat.timezone'))->endOfDay();
            $startBoundary = $endBoundary->copy()->subDays($activeDays - 1)->startOfDay();

            $dailyStats = ZakatTransaction::query()
                ->select(
                    DB::raw('DATE(COALESCE(waktu_terima, created_at)) as date'),
                    DB::raw('COUNT(DISTINCT no_transaksi) as count')
                )
                ->valid()
                ->where('tahun_zakat', $activeYear)
                ->whereRaw('COALESCE(waktu_terima, created_at) >= ?', [$startBoundary])
                ->whereRaw('COALESCE(waktu_terima, created_at) <= ?', [$endBoundary])
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();

            $statsMap = $dailyStats->pluck('count', 'date');
            $labels   = [];
            $values   = [];

            for ($i = $activeDays - 1; $i >= 0; $i--) {
                $currentDate = $endBoundary->copy()->subDays($i)->startOfDay();
                $dateStr     = $currentDate->format('Y-m-d');
                $labels[]    = $currentDate->locale('id')->translatedFormat('d M');
                $values[]    = (int) ($statsMap[$dateStr] ?? 0);
            }

            return ['labels' => $labels, 'values' => $values];
        });

        $years = ViewOptions::years($activeYear);

        return view('dashboard', [
            'activeYear'       => $activeYear,
            'year'             => $year,
            'metode'           => $metode,
            'years'            => $years,
            'methods'          => ZakatTransaction::METHODS,
            'payload'          => $payload,
            'latestTransactions' => $latestTransactions,
            'chartData'        => $chartData,
            'activeDays'       => $activeDays,
            'offSeason'        => $offSeason,
            'lastActiveDate'   => $lastActiveDate,
            'chartPeriodLabel' => $chartPeriodLabel,
        ]);
    }

    private function resolveActiveDays(Request $request): int
    {
        $defaultActiveDays = (int) config('zakat.dashboard.default_active_days', 14);
        $allowedActiveDays = (array) config('zakat.dashboard.allowed_active_days', [7, 14, 30]);

        $activeDays = $request->integer('days', $defaultActiveDays);
        if (!in_array($activeDays, $allowedActiveDays, true)) {
            return $defaultActiveDays;
        }

        return $activeDays;
    }
}

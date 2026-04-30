<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Support\ViewOptions;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuestPagesController extends Controller
{
    public function home(Request $request)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $years = ViewOptions::years($activeYear);

        $selectedYear = (int) ($request->integer('year') ?: $activeYear);
        if (!in_array($selectedYear, $years, true)) {
            $selectedYear = $activeYear;
        }

        $rawRefresh = AppSetting::getInt(AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS, 15);
        $refreshIntervalSeconds = AppSetting::normalizePublicRefreshIntervalSeconds($rawRefresh, 15);

        // Cache Key for Home Page Stats (Historical + Daily)
        $cacheKey = "public_home_stats_{$selectedYear}";
        
        $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, $refreshIntervalSeconds, function() use ($selectedYear) {
            // Fetch historical data for Line Chart
            $historicalData = ZakatTransaction::select('tahun_zakat', DB::raw('SUM(nominal_uang) as total_uang'))
                ->where('status', ZakatTransaction::STATUS_VALID)
                ->whereNotNull('tahun_zakat')
                ->groupBy('tahun_zakat')
                ->orderBy('tahun_zakat', 'asc')
                ->get();

            $chartLabels = $historicalData->pluck('tahun_zakat')->toArray();
            $chartData = $historicalData->pluck('total_uang')->toArray();

            $rekap = \App\Support\RekapBuilder::build($selectedYear);
            
            return [
                'historicalChartData' => [
                    'labels' => $chartLabels,
                    'data' => $chartData,
                ],
                'dailyChartData' => \App\Support\RekapBuilder::buildDailyChartData($selectedYear)
            ];
        });

        return view('public.home', array_merge([
            'brand' => config('app.name', 'ZakatAnNur'),
            'years' => $years,
            'selectedYear' => $selectedYear,
            'refreshIntervalSeconds' => $refreshIntervalSeconds,
        ], $stats));
    }
}

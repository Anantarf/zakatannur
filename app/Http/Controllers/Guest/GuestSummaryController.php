<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Support\RekapBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GuestSummaryController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->integer('year');
        if (!$year) {
            $year = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        }

        if ($year < 2000 || $year > 2100) {
            return response()->json(['message' => 'Parameter year tidak valid.'], 422);
        }

        $cacheTtlSeconds = AppSetting::normalizePublicRefreshIntervalSeconds(
            AppSetting::getInt(AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS, 15), 15
        );
        $cacheKey = 'public_summary_year_' . $year;
        $isFresh = !Cache::has($cacheKey);

        $payload = Cache::remember($cacheKey, $cacheTtlSeconds, function () use ($year) {
            $rekap = RekapBuilder::build($year);

            return [
                'year' => $year,
                'computed_at_wib' => now('Asia/Jakarta')->format('d/m/Y H:i:s'),
                'items' => $rekap['items'],
                'totals' => $rekap['totals'],
                'dailyChartData' => RekapBuilder::buildDailyChartData($year),
            ];
        });

        return response()->json([
            'status' => $isFresh ? 'FRESH' : 'CACHED',
            'updated_at_wib' => $payload['computed_at_wib'] . ' WIB',
            'data' => $payload,
        ]);
    }
}

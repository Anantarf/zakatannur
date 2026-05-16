<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Services\DashboardRekapFilters;
use App\Services\DashboardInsightsService;
use App\Services\Transactions\GroupedTransactionQueryService;
use App\Support\RekapBuilder;
use App\Support\ViewOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function show(
        Request $request,
        GroupedTransactionQueryService $groupedQueryService,
        DashboardInsightsService $dashboardInsightsService
    )
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $filters = DashboardRekapFilters::fromRequest($request);
        $year = $filters->year;
        $metode = $filters->metode;
        $activeDays = $this->resolveActiveDays($request);

        $yearKey = $year ?? 'all';
        $metodeKey = $metode ?? 'all';
        $payload = Cache::remember(
            AppSetting::cacheKeyForDashboardRekap($yearKey, $metodeKey),
            (int) config('zakat.cache.public_summary_ttl', 300),
            fn() => RekapBuilder::build($year, $metode)
        );

        $latestTransactions = $groupedQueryService->latestValid($year, $metode, 10);
        $insights = $dashboardInsightsService->buildInsights($activeYear, $activeDays);

        return view('dashboard', [
            'activeYear' => $activeYear,
            'year' => $year,
            'metode' => $metode,
            'years' => ViewOptions::years($activeYear),
            'methods' => ZakatTransaction::METHODS,
            'payload' => $payload,
            'latestTransactions' => $latestTransactions,
            'chartData' => $insights['chartData'],
            'activeDays' => $activeDays,
            'offSeason' => $insights['offSeason'],
            'lastActiveDate' => $insights['lastActiveDate'],
            'chartPeriodLabel' => $insights['chartPeriodLabel'],
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

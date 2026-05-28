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
        $periodId = $filters->periodId;
        $metode = $filters->metode;
        $activeDays = $this->resolveActiveDays($request);

        $yearKey = $year ?? 'all';
        $periodKey = $periodId ?? 'all';
        $metodeKey = $metode ?? 'all';
        $payload = Cache::remember(
            AppSetting::cacheKeyForDashboardRekap($yearKey . '_period_' . $periodKey, $metodeKey),
            (int) config('zakat.cache.public_summary_ttl', 300),
            fn() => RekapBuilder::build($year, $metode, $periodId)
        );

        $latestTransactions = $groupedQueryService->latestValidGroups($year, $metode, 10, $periodId);
        $insights = $dashboardInsightsService->buildInsights($year, $activeDays, $periodId, $metode);
        $chartYear = (int) ($insights['chartRange']['year'] ?? $activeYear);
        $chartPeriodId = $insights['chartRange']['period_id'] ?? null;
        $selectedPeriod = collect(ViewOptions::periods())->firstWhere('id', $periodId);
        $dashboardScopeLabel = $selectedPeriod
            ? $selectedPeriod->display_label . ($selectedPeriod->sequence > 1 ? ' #' . $selectedPeriod->sequence : '')
            : ($year ? 'Tahun ' . $year : 'Semua Waktu');

        return view('dashboard', [
            'activeYear' => $activeYear,
            'chartYear' => $chartYear,
            'chartPeriodId' => $chartPeriodId,
            'year' => $year,
            'periodId' => $periodId,
            'metode' => $metode,
            'years' => ViewOptions::years($activeYear),
            'periods' => ViewOptions::periods(),
            'methods' => ZakatTransaction::METHODS,
            'payload' => $payload,
            'latestTransactions' => $latestTransactions,
            'chartData' => $insights['chartData'],
            'activeDays' => $activeDays,
            'offSeason' => $insights['offSeason'],
            'lastActiveDate' => $insights['lastActiveDate'],
            'chartPeriodLabel' => $insights['chartPeriodLabel'],
            'workspace' => $insights['workspace'],
            'dashboardScopeLabel' => $dashboardScopeLabel,
            'dashboardChartRange' => $insights['chartRange'],
            'dashboardChartSourceNote' => $this->describeChartSource($insights['chartRange']),
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

    private function describeChartSource(array $range): ?string
    {
        $source = (string) ($range['source'] ?? '');

        return match ($source) {
            'dashboard_manual_period' => 'Grafik ini sedang memakai periode pilihan manual dari Pengaturan Admin.',
            'dashboard_active_period' => 'Grafik ini sedang mengikuti periode aktif yang dipakai sistem saat ini.',
            'dashboard_last_completed_period' => 'Grafik ini sedang memakai periode terakhir yang sudah selesai.',
            'dashboard_last_completed_period_fallback_active' => 'Belum ada periode yang selesai, jadi grafik sementara memakai periode aktif.',
            'dashboard_last_completed_period_fallback_year' => 'Belum ada periode yang selesai, jadi grafik sementara memakai tahun aktif.',
            'requested_period' => 'Grafik ini sedang mengikuti periode yang diminta langsung dari halaman.',
            'configured' => 'Grafik ini sedang memakai range tanggal yang diatur manual.',
            default => null,
        };
    }
}

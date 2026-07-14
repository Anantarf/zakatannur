<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\PublicSummaryService;
use App\Support\ViewOptions;
use Illuminate\Http\Request;

class GuestPagesController extends Controller
{
    public function home(Request $request, PublicSummaryService $publicSummaryService)
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);
        $years = ViewOptions::years($activeYear);
        $selectedYear = $this->resolveSelectedYear($request, $activeYear, $years);
        $homeData = $publicSummaryService->homePageData($selectedYear);

        return view('public.home', array_merge(
            [
                'brand' => config('app.name', 'ZakatAnNur'),
                'years' => $years,
                'selectedYear' => $selectedYear,
            ],
            $homeData
        ));
    }

    public function konsultasi()
    {
        return view('public.konsultasi');
    }

    private function resolveSelectedYear(Request $request, int $activeYear, array $years): int
    {
        $selectedYear = (int) ($request->integer('year') ?: $activeYear);

        if (!in_array($selectedYear, $years, true)) {
            return $activeYear;
        }

        return $selectedYear;
    }
}

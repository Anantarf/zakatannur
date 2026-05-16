<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Reporting\TransactionExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function exportDaily(Request $request, TransactionExportService $exportService)
    {
        $date = $this->validateDailyExportRequest($request);
        return $exportService->exportDaily($date);
    }

    private function validateDailyExportRequest(Request $request): string
    {
        $validated = $request->validate(['date' => 'required|date_format:Y-m-d']);
        return (string) $validated['date'];
    }

    public function exportYearly(Request $request, TransactionExportService $exportService)
    {
        $year = $this->validateYearlyExportRequest($request);
        return $exportService->exportYearly($year);
    }

    private function validateYearlyExportRequest(Request $request): int
    {
        $yearMin = (int) config('zakat.year_bounds.min', 2000);
        $yearMax = (int) config('zakat.year_bounds.max', 2100);

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:' . $yearMin, 'max:' . $yearMax],
        ]);

        return (int) $validated['year'];
    }
}

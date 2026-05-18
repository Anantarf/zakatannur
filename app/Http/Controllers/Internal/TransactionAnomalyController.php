<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;
use App\Services\Transactions\TransactionAnomalyService;
use App\Services\Transactions\TransactionReviewAssistantService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionAnomalyController extends Controller
{
    public function index(Request $request, TransactionAnomalyService $anomalyService): View
    {
        $filters = $anomalyService->parseFilters($request);
        $groups = $anomalyService->paginatedGroups($filters, $request->query());

        return view('internal.anomalies.index', array_merge(
            $anomalyService->indexViewData($filters),
            ['groups' => $groups]
        ));
    }

    public function show(string $noTransaksi, TransactionAnomalyService $anomalyService): View
    {
        return view('internal.anomalies.show', $anomalyService->detailViewData($noTransaksi));
    }

    public function updateReviewStatus(
        Request $request,
        string $noTransaksi,
        TransactionReviewAssistantService $reviewAssistantService
    ): RedirectResponse {
        $validated = $request->validate([
            'review_status' => ['required', 'string', Rule::in(TransactionRiskReview::REVIEW_STATUSES)],
        ]);

        $tx = ZakatTransaction::query()->where('no_transaksi', $noTransaksi)->firstOrFail();
        $beforeReview = $reviewAssistantService->detailReviewForGroup($noTransaksi);
        $reviewAssistantService->updateGroupReviewStatus($noTransaksi, $validated['review_status'], (int) $request->user()->id);
        $afterReview = $reviewAssistantService->detailReviewForGroup($noTransaksi);

        if (($beforeReview['review_status'] ?? null) !== ($afterReview['review_status'] ?? null)) {
            Audit::log($request, 'transaction.risk_review_status_updated', $tx, [
                'no_transaksi' => $noTransaksi,
                'previous_review_status' => $beforeReview['review_status'] ?? null,
                'new_review_status' => $afterReview['review_status'] ?? null,
                'risk_level' => $afterReview['risk_level'] ?? null,
            ]);
        }

        return redirect()
            ->route('internal.anomalies.show', ['noTransaksi' => $noTransaksi])
            ->with('status', 'Status review anomali berhasil diperbarui.');
    }
}

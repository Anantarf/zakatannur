<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;
use App\Services\Admin\ZakkyAdminInsightService;
use App\Services\Transactions\TransactionAnomalyService;
use App\Services\Transactions\TransactionReviewAssistantService;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionAnomalyController extends Controller
{
    public function index(
        Request $request,
        TransactionAnomalyService $anomalyService,
        ZakkyAdminInsightService $zakkyInsightService
    ): View
    {
        $filters = $anomalyService->parseFilters($request);
        $viewData = $anomalyService->indexViewData($filters);
        $groups = $anomalyService->paginatedGroups($filters, $request->query());
        $zakkyInsight = $zakkyInsightService->anomalyListInsight($viewData['overview']);

        return view('internal.anomalies.index', array_merge(
            $viewData,
            ['groups' => $groups, 'zakkyInsight' => $zakkyInsight]
        ));
    }

    public function show(
        string $noTransaksi,
        TransactionAnomalyService $anomalyService,
        ZakkyAdminInsightService $zakkyInsightService
    ): View
    {
        $viewData = $anomalyService->detailViewData($noTransaksi);
        $viewData['zakkyInsight'] = $zakkyInsightService->anomalyDetailInsight(
            $viewData['riskReview'],
            $viewData['riskMeta'],
        );

        return view('internal.anomalies.show', $viewData);
    }

    public function updateReviewStatus(
        Request $request,
        string $noTransaksi,
        TransactionReviewAssistantService $reviewAssistantService
    ): RedirectResponse {
        $validated = $request->validate([
            'review_status' => ['required', 'string', Rule::in(TransactionRiskReview::REVIEW_STATUSES)],
            'review_note' => [
                Rule::requiredIf(fn () => $request->input('review_status') === TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $tx = ZakatTransaction::query()->where('no_transaksi', $noTransaksi)->firstOrFail();
        $beforeReview = $reviewAssistantService->detailReviewForGroup($noTransaksi);
        $reviewNote = isset($validated['review_note']) ? trim((string) $validated['review_note']) : null;
        $reviewAssistantService->updateGroupReviewStatus(
            $noTransaksi,
            $validated['review_status'],
            filled($reviewNote) ? $reviewNote : null,
            (int) $request->user()->id
        );
        TransactionAnomalyService::bustOverviewCache();
        $afterReview = $reviewAssistantService->detailReviewForGroup($noTransaksi);

        if (
            ($beforeReview['review_status'] ?? null) !== ($afterReview['review_status'] ?? null)
            || ($beforeReview['review_note'] ?? null) !== ($afterReview['review_note'] ?? null)
        ) {
            Audit::log($request, 'transaction.risk_review_status_updated', $tx, [
                'no_transaksi' => $noTransaksi,
                'previous_review_status' => $beforeReview['review_status'] ?? null,
                'new_review_status' => $afterReview['review_status'] ?? null,
                'previous_review_note' => $beforeReview['review_note'] ?? null,
                'new_review_note' => $afterReview['review_note'] ?? null,
                'risk_level' => $afterReview['risk_level'] ?? null,
            ]);
        }

        return redirect()
            ->route('internal.anomalies.show', ['noTransaksi' => $noTransaksi])
            ->with('status', 'Status anomali diperbarui.');
    }
}

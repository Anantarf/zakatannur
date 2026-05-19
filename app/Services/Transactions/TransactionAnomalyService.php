<?php

namespace App\Services\Transactions;

use App\Models\AppSetting;
use App\Models\TransactionRiskReview;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Support\SqlDialect;
use App\Support\ViewOptions;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionAnomalyService
{
    private const FLAG_LABELS = [
        'exact_duplicate' => 'Potensi transaksi ganda',
        'updated_after_receipt_printed' => 'Diubah setelah kwitansi tercetak',
        'restored_after_delete' => 'Direstore setelah dihapus',
        'significant_nominal_change' => 'Perubahan nominal signifikan',
        'infaq_outlier' => 'Nominal infaq tidak biasa',
    ];

    public function __construct(
        private GroupedTransactionQueryService $groupedQueryService,
        private TransactionReviewAssistantService $reviewAssistantService,
    ) {
    }

    public function parseFilters(Request $request): array
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $validated = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:' . (int) config('zakat.validation.search_query_max', 100)],
            'year' => ['nullable', 'integer', 'min:' . (int) config('zakat.year_bounds.min', 2000), 'max:' . (int) config('zakat.year_bounds.max', 2100)],
            'period_id' => ['nullable', 'integer', 'exists:zakat_periods,id'],
            'scope' => ['nullable', 'string', Rule::in(['active', 'archived'])],
            'risk_level' => ['nullable', 'string', Rule::in(TransactionRiskReview::LEVELS)],
            'review_status' => ['nullable', 'string', Rule::in(TransactionRiskReview::REVIEW_STATUSES)],
            'flag_type' => ['nullable', 'string', Rule::in(array_keys(self::FLAG_LABELS))],
            'petugas_id' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();

        $periodId = isset($validated['period_id']) ? (int) $validated['period_id'] : null;
        $year = array_key_exists('year', $validated)
            ? ($validated['year'] !== null ? (int) $validated['year'] : null)
            : $activeYear;

        if ($periodId !== null && !array_key_exists('year', $validated)) {
            $year = null;
        }

        return [
            'q' => isset($validated['q']) ? trim((string) $validated['q']) : '',
            'year' => $year,
            'periodId' => $periodId,
            'scope' => $validated['scope'] ?? 'active',
            'risk_level' => $validated['risk_level'] ?? null,
            'review_status' => $validated['review_status'] ?? null,
            'flag_type' => $validated['flag_type'] ?? null,
            'petugasId' => isset($validated['petugas_id']) ? (int) $validated['petugas_id'] : null,
            'activeYear' => $activeYear,
        ];
    }

    public function indexViewData(array $filters): array
    {
        return array_merge($filters, [
            'overview' => $this->overview($filters),
            'riskLevels' => TransactionRiskReview::LEVELS,
            'reviewStatuses' => TransactionRiskReview::REVIEW_STATUSES,
            'flagOptions' => self::FLAG_LABELS,
            'petugasOptions' => ViewOptions::petugasOptions(),
            'years' => ViewOptions::years($filters['activeYear']),
            'periods' => ViewOptions::periods(),
        ]);
    }

    public function paginatedGroups(array $filters, array $queryParams): LengthAwarePaginator
    {
        $groups = $this->baseQuery($filters)
            ->orderByRaw(SqlDialect::maxEffectiveTimestampOrder())
            ->orderByDesc('no_transaksi')
            ->paginate(20)
            ->appends($queryParams);

        $this->attachListSummaries($groups);

        return $groups;
    }

    public function detailViewData(string $noTransaksi): array
    {
        $transactions = ZakatTransaction::query()
            ->with(['muzakki' => fn ($query) => $query->withTrashed(), 'petugas'])
            ->where('no_transaksi', $noTransaksi)
            ->orderBy('id')
            ->get();

        if ($transactions->isEmpty()) {
            abort(404);
        }

        $mainTx = $transactions->first();
        $riskReview = $this->reviewAssistantService->detailReviewForGroup($noTransaksi);
        $riskMeta = $this->groupRiskMeta(collect([$noTransaksi]))[$noTransaksi] ?? [
            'primary_flag' => null,
            'flag_labels' => [],
            'flags_count' => 0,
        ];

        $receiptPrintedAt = $transactions->pluck('receipt_printed_at')->filter()->max();
        $receiptPrintedBy = $transactions->firstWhere('receipt_printed_at', '!=', null)?->receipt_printed_by;

        return [
            'mainTx' => $mainTx,
            'transactions' => $transactions,
            'groupedArr' => $transactions->groupBy(fn ($transaction) => $transaction->muzakki?->name ?? '-'),
            'noTransaksi' => $noTransaksi,
            'totalUang' => (int) $transactions->where('metode', '!=', ZakatTransaction::METHOD_BERAS)->sum('nominal_uang'),
            'totalTf' => (int) $transactions->where('metode', ZakatTransaction::METHOD_UANG)->where('is_transfer', true)->sum('nominal_uang'),
            'totalBeras' => (float) $transactions->where('metode', ZakatTransaction::METHOD_BERAS)->sum('jumlah_beras_kg'),
            'riskReview' => $riskReview,
            'riskMeta' => $riskMeta,
            'reviewStatuses' => TransactionRiskReview::REVIEW_STATUSES,
            'receiptPrintedAt' => $receiptPrintedAt ? Carbon::parse($receiptPrintedAt) : null,
            'receiptPrintedByName' => $receiptPrintedBy ? User::query()->find($receiptPrintedBy)?->name : null,
        ];
    }

    public static function flagLabels(): array
    {
        return self::FLAG_LABELS;
    }

    private function overview(array $filters): array
    {
        $summary = DB::query()
            ->fromSub($this->baseQuery($filters), 'anomaly_rows')
            ->selectRaw('COUNT(*) as total_groups')
            ->selectRaw('SUM(CASE WHEN risk_severity = 3 THEN 1 ELSE 0 END) as suspicious_groups')
            ->selectRaw('SUM(CASE WHEN risk_severity = 2 THEN 1 ELSE 0 END) as warning_groups')
            ->selectRaw('SUM(CASE WHEN review_severity = 1 THEN 1 ELSE 0 END) as pending_review_groups')
            ->selectRaw('SUM(CASE WHEN review_severity = 2 THEN 1 ELSE 0 END) as safe_review_groups')
            ->selectRaw('SUM(CASE WHEN review_severity = 3 THEN 1 ELSE 0 END) as follow_up_groups')
            ->first();

        return [
            'totalGroups' => (int) ($summary->total_groups ?? 0),
            'suspiciousGroups' => (int) ($summary->suspicious_groups ?? 0),
            'warningGroups' => (int) ($summary->warning_groups ?? 0),
            'pendingReviewGroups' => (int) ($summary->pending_review_groups ?? 0),
            'safeReviewGroups' => (int) ($summary->safe_review_groups ?? 0),
            'followUpGroups' => (int) ($summary->follow_up_groups ?? 0),
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        $reviewSummary = $this->reviewAssistantService->historySummarySubquery();
        $matchingGroupNos = null;

        if ($filters['flag_type'] ?? null) {
            $flagNeedle = '%"' . str_replace(['\\', '%'], ['\\\\', '\\%'], $filters['flag_type']) . '"%';
            $matchingGroupNos = TransactionRiskReview::query()
                ->where('risk_flags', 'like', $flagNeedle)
                ->pluck('group_no_transaksi')
                ->unique()
                ->values()
                ->all();
        }

        return $this->groupedQueryService->make()
            ->with(['petugas'])
            ->filter($filters)
            ->joinSub($reviewSummary, 'risk_reviews', function ($join) {
                $join->on('zakat_transactions.no_transaksi', '=', 'risk_reviews.group_no_transaksi');
            })
            ->selectRaw('MAX(COALESCE(risk_reviews.risk_severity, 0)) as risk_severity')
            ->selectRaw('MAX(COALESCE(risk_reviews.review_severity, 0)) as review_severity')
            ->where('risk_reviews.risk_severity', '>=', 2)
            ->when(($filters['scope'] ?? 'active') === 'active', function (Builder $query) {
                $query->where('risk_reviews.review_severity', '!=', $this->reviewAssistantService->sqlReviewSeverity(TransactionRiskReview::REVIEW_AMAN));
            })
            ->when(($filters['scope'] ?? 'active') === 'archived', function (Builder $query) {
                $query->where('risk_reviews.review_severity', $this->reviewAssistantService->sqlReviewSeverity(TransactionRiskReview::REVIEW_AMAN));
            })
            ->when($filters['risk_level'] ?? null, function (Builder $query, string $riskLevel) {
                $query->where('risk_reviews.risk_severity', $this->reviewAssistantService->sqlRiskSeverity($riskLevel));
            })
            ->when($filters['review_status'] ?? null, function (Builder $query, string $reviewStatus) {
                $query->where('risk_reviews.review_severity', $this->reviewAssistantService->sqlReviewSeverity($reviewStatus));
            })
            ->when($matchingGroupNos !== null, function (Builder $query) use ($matchingGroupNos) {
                $query->whereIn('zakat_transactions.no_transaksi', $matchingGroupNos === [] ? ['__no_matching_group__'] : $matchingGroupNos);
            })
            ->groupBy('no_transaksi');
    }

    private function attachListSummaries(LengthAwarePaginator $groups): void
    {
        $groupNos = collect($groups->items())->pluck('no_transaksi')->filter()->values();
        if ($groupNos->isEmpty()) {
            return;
        }

        $this->reviewAssistantService->attachHistorySummaries($groups);
        $riskMeta = $this->groupRiskMeta($groupNos);

        $groups->getCollection()->transform(function ($group) use ($riskMeta) {
            $meta = $riskMeta[$group->no_transaksi] ?? [
                'primary_flag' => null,
                'flag_labels' => [],
                'flags_count' => 0,
            ];

            $group->primary_flag = $meta['primary_flag'];
            $group->flag_labels = $meta['flag_labels'];
            $group->flags_count = $meta['flags_count'];

            return $group;
        });
    }

    private function groupRiskMeta(Collection $groupNos): array
    {
        return TransactionRiskReview::query()
            ->select(['group_no_transaksi', 'risk_flags'])
            ->whereIn('group_no_transaksi', $groupNos->all())
            ->get()
            ->groupBy('group_no_transaksi')
            ->map(function (Collection $reviews) {
                $flags = $reviews
                    ->pluck('risk_flags')
                    ->flatten(1)
                    ->filter()
                    ->values();

                $uniqueFlags = $flags->unique()->values();
                $primaryFlag = $uniqueFlags->first();

                return [
                    'primary_flag' => $primaryFlag,
                    'flag_labels' => $uniqueFlags
                        ->map(fn (string $flag) => self::FLAG_LABELS[$flag] ?? str_replace('_', ' ', $flag))
                        ->all(),
                    'flags_count' => $uniqueFlags->count(),
                ];
            })
            ->all();
    }
}

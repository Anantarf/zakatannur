<?php

namespace App\Services\Transactions;

use App\Models\AppSetting;
use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;
use App\Support\SqlDialect;
use App\Support\ViewOptions;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionHistoryService
{
    public function __construct(
        private GroupedTransactionQueryService $groupedQueryService,
        private TransactionReviewAssistantService $reviewAssistantService,
    ) {
    }

    /**
     * @return TransactionHistoryFilters
     */
    public function parseFilters(Request $request, bool $canViewRisk): TransactionHistoryFilters
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $validated = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:' . (int) config('zakat.validation.search_query_max', 100)],
            'year' => ['nullable', 'integer', 'min:' . (int) config('zakat.year_bounds.min', 2000), 'max:' . (int) config('zakat.year_bounds.max', 2100)],
            'category' => ['nullable', 'string', Rule::in(ZakatTransaction::CATEGORIES)],
            'metode' => ['nullable', 'string', Rule::in(ZakatTransaction::METHODS)],
            'status' => ['nullable', 'string', Rule::in(ZakatTransaction::STATUSES)],
            'petugas_id' => ['nullable', 'integer', 'exists:users,id'],
            'risk_level' => ['nullable', 'string', Rule::in(TransactionRiskReview::LEVELS)],
            'review_status' => ['nullable', 'string', Rule::in(TransactionRiskReview::REVIEW_STATUSES)],
        ])->validate();

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $year = array_key_exists('year', $validated)
            ? ($validated['year'] !== null ? (int) $validated['year'] : null)
            : $activeYear;

        return TransactionHistoryFilters::fromArray([
            'q' => $q,
            'year' => $year,
            'category' => $validated['category'] ?? null,
            'metode' => $validated['metode'] ?? null,
            'status' => $validated['status'] ?? null,
            'petugasId' => isset($validated['petugas_id']) ? (int) $validated['petugas_id'] : null,
            'riskLevel' => $canViewRisk ? ($validated['risk_level'] ?? null) : null,
            'reviewStatus' => $canViewRisk ? ($validated['review_status'] ?? null) : null,
            'activeYear' => $activeYear,
        ]);
    }

    public function paginatedHistory(TransactionHistoryFilters $filters, array $queryParams, bool $canViewRisk): LengthAwarePaginator
    {
        $transactions = $this->baseHistoryQuery($filters, $canViewRisk)
            ->orderByRaw(SqlDialect::maxEffectiveTimestampOrder())
            ->orderByDesc('no_transaksi')
            ->paginate(20)
            ->appends($queryParams);

        if ($canViewRisk) {
            $this->reviewAssistantService->attachHistorySummaries($transactions);
        }

        return $transactions;
    }

    public function historyOverview(TransactionHistoryFilters $filters, bool $canViewRisk): array
    {
        if (!$canViewRisk) {
            return [
                'totalGroups' => 0,
                'riskyGroups' => 0,
                'suspiciousGroups' => 0,
                'pendingReviewGroups' => 0,
                'safeReviewGroups' => 0,
            ];
        }

        $summary = DB::query()
            ->fromSub($this->baseHistoryQuery($filters, true), 'history_rows')
            ->selectRaw('COUNT(*) as total_groups')
            ->selectRaw('SUM(CASE WHEN risk_severity >= 2 THEN 1 ELSE 0 END) as risky_groups')
            ->selectRaw('SUM(CASE WHEN risk_severity = 3 THEN 1 ELSE 0 END) as suspicious_groups')
            ->selectRaw('SUM(CASE WHEN review_severity = 1 THEN 1 ELSE 0 END) as pending_review_groups')
            ->selectRaw('SUM(CASE WHEN review_severity = 2 THEN 1 ELSE 0 END) as safe_review_groups')
            ->first();

        return [
            'totalGroups' => (int) ($summary->total_groups ?? 0),
            'riskyGroups' => (int) ($summary->risky_groups ?? 0),
            'suspiciousGroups' => (int) ($summary->suspicious_groups ?? 0),
            'pendingReviewGroups' => (int) ($summary->pending_review_groups ?? 0),
            'safeReviewGroups' => (int) ($summary->safe_review_groups ?? 0),
        ];
    }

    public function paginatedTrash(string $query, array $queryParams): LengthAwarePaginator
    {
        $transactions = $this->groupedQueryService->make(true)
            ->with(['petugas'])
            ->when($query !== '', function (Builder $builder) use ($query) {
                $like = '%' . str_replace('%', '\\%', $query) . '%';
                $builder->where(function (Builder $subQuery) use ($like) {
                    $subQuery->where('no_transaksi', 'like', $like)
                        ->orWhere('pembayar_nama', 'like', $like);
                });
            })
            ->groupBy('no_transaksi')
            ->orderByDesc('deleted_at')
            ->paginate(20)
            ->appends($queryParams);

        $purgeDays = (int) config('zakat.retention.purge_days', 30);

        $transactions->getCollection()->transform(function ($transaction) use ($purgeDays) {
            $deletedAt = $transaction->deleted_at
                ? Carbon::parse($transaction->deleted_at)->setTimezone(config('zakat.timezone'))
                : null;

            $transaction->days_left = $deletedAt
                ? max(0, $purgeDays - (int) $deletedAt->startOfDay()->diffInDays(now(config('zakat.timezone'))->startOfDay()))
                : null;
            $transaction->deleted_at_formatted = $deletedAt ? $deletedAt->format('d/m/Y H:i') : '-';

            return $transaction;
        });

        return $transactions;
    }

    public function indexViewData(TransactionHistoryFilters $filters, bool $canViewRisk): array
    {
        $effectiveTimestamp = SqlDialect::effectiveTimestamp();

        $availableDates = ZakatTransaction::valid()
            ->selectRaw('DISTINCT ' . SqlDialect::dateExpression($effectiveTimestamp, 'date'))
            ->orderByDesc('date')
            ->pluck('date')
            ->mapWithKeys(function ($date) {
                return [$date => Carbon::parse($date)->locale('id')->translatedFormat('d F Y')];
            });

        return array_merge($filters->toArray(), [
            'historyOverview' => $this->historyOverview($filters, $canViewRisk),
            'years' => ViewOptions::years($filters->activeYear),
            'categories' => ZakatTransaction::CATEGORIES,
            'methods' => ZakatTransaction::METHODS,
            'statuses' => ZakatTransaction::STATUSES,
            'riskLevels' => $canViewRisk ? TransactionRiskReview::LEVELS : [],
            'reviewStatuses' => $canViewRisk ? TransactionRiskReview::REVIEW_STATUSES : [],
            'petugasOptions' => ViewOptions::petugasOptions(),
            'availableDates' => $availableDates,
            'availableYears' => ZakatTransaction::valid()
                ->distinct()
                ->orderByDesc('tahun_zakat')
                ->pluck('tahun_zakat'),
        ]);
    }

    private function baseHistoryQuery(TransactionHistoryFilters $filters, bool $canViewRisk): Builder
    {
        $query = $this->groupedQueryService->make()
            ->with(['petugas'])
            ->filter($filters->toArray());

        if (!$canViewRisk) {
            return $query->groupBy('no_transaksi');
        }

        $reviewSummary = $this->reviewAssistantService->historySummarySubquery();

        return $query
            ->leftJoinSub($reviewSummary, 'risk_reviews', function ($join) {
                $join->on('zakat_transactions.no_transaksi', '=', 'risk_reviews.group_no_transaksi');
            })
            ->selectRaw('MAX(COALESCE(risk_reviews.risk_severity, 0)) as risk_severity')
            ->selectRaw('MAX(COALESCE(risk_reviews.review_severity, 0)) as review_severity')
            ->when($filters->riskLevel !== null, function (Builder $query) use ($filters) {
                $query->where('risk_reviews.risk_severity', $this->reviewAssistantService->sqlRiskSeverity($filters->riskLevel));
            })
            ->when($filters->reviewStatus !== null, function (Builder $query) use ($filters) {
                $query->where('risk_reviews.review_severity', $this->reviewAssistantService->sqlReviewSeverity($filters->reviewStatus));
            })
            ->groupBy('no_transaksi');
    }
}

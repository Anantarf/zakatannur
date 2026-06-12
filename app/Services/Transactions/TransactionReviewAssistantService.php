<?php

namespace App\Services\Transactions;

use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionReviewAssistantService
{
    public function __construct(
        private TransactionRiskAnalyzer $riskAnalyzer,
    ) {
    }

    public function syncForTransactions(iterable $transactions): void
    {
        foreach ($transactions as $transaction) {
            if (!$transaction instanceof ZakatTransaction) {
                continue;
            }

            $analysis = $this->riskAnalyzer->analyze($transaction);
            $review = TransactionRiskReview::query()->firstOrNew([
                'zakat_transaction_id' => $transaction->id,
            ]);

            $review->fill([
                'group_no_transaksi' => $transaction->no_transaksi,
                'risk_level' => $analysis['risk_level'],
                'risk_score' => $analysis['risk_score'],
                'risk_flags' => $analysis['risk_flags'],
                'reasons' => $analysis['reasons'],
                'duplicate_candidates' => $analysis['duplicate_candidates'],
                'detector_version' => $analysis['detector_version'],
                'review_status' => $review->review_status ?: TransactionRiskReview::REVIEW_BELUM_DITINJAU,
                'reviewed_by' => $review->reviewed_by,
                'reviewed_at' => $review->reviewed_at,
                'checked_at' => now(config('zakat.timezone')),
            ]);

            $review->save();
        }
    }

    public function attachHistorySummaries($paginator): void
    {
        $groupNos = collect($paginator->items())->pluck('no_transaksi')->filter()->values();
        $summaries = $this->groupReviewSummaries($groupNos);

        $paginator->getCollection()->transform(function ($transaction) use ($summaries) {
            $summary = $summaries[$transaction->no_transaksi] ?? null;
            $transaction->risk_level = $summary['risk_level'] ?? null;
            $transaction->risk_score = $summary['risk_score'] ?? 0;
            $transaction->review_status = $summary['review_status'] ?? null;
            return $transaction;
        });
    }

    public function detailReviewForGroup(string $noTransaksi): array
    {
        return $this->groupReviewSummary($noTransaksi);
    }

    public function reviewPayloadForTransaction(int $transactionId): array
    {
        $transaction = ZakatTransaction::query()->findOrFail($transactionId);

        return $this->detailReviewForGroup($transaction->no_transaksi);
    }

    public function updateGroupReviewStatus(string $noTransaksi, string $status, ?string $reviewNote, int $reviewerId): void
    {
        TransactionRiskReview::query()
            ->where('group_no_transaksi', $noTransaksi)
            ->update([
                'review_status' => $status,
                'review_note' => $reviewNote,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(config('zakat.timezone')),
            ]);
    }

    public function historySummarySubquery(): Builder
    {
        [$reviewSeveritySql, $reviewSeverityBindings] = $this->reviewSeveritySummarySql();

        return $this->activeReviewsQuery()
            ->selectRaw('transaction_risk_reviews.group_no_transaksi')
            ->selectRaw('MAX(CASE risk_level WHEN ? THEN 2 ELSE 1 END) as risk_severity', [
                TransactionRiskReview::LEVEL_WARNING,
            ])
            ->selectRaw('MAX(COALESCE(transaction_risk_reviews.risk_score, 0)) as risk_score_max')
            ->selectRaw($reviewSeveritySql, $reviewSeverityBindings)
            ->groupBy('group_no_transaksi');
    }

    public function groupReviewSummary(string $groupNoTransaksi): array
    {
        $reviews = $this->activeReviewsQuery()
            ->with(['reviewer', 'transaction.muzakki' => fn ($query) => $query->withTrashed()])
            ->where('group_no_transaksi', $groupNoTransaksi)
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'risk_level' => null,
                'risk_score' => 0,
                'review_status' => null,
                'review_note' => null,
                'reasons' => [],
                'duplicate_candidates' => [],
                'summary_text' => 'Belum ada hasil analisis risiko untuk transaksi ini.',
                'reviewed_by_name' => null,
                'reviewed_at' => null,
            ];
        }

        $riskLevel = $this->aggregateRiskLevel($reviews);
        $reviewStatus = $this->aggregateReviewStatus($reviews);
        $reviewedAt = $reviews->max('reviewed_at');

        $latestReviewed = $reviews
            ->filter(fn (TransactionRiskReview $review) => $review->reviewed_at !== null)
            ->sortByDesc(fn (TransactionRiskReview $review) => $review->reviewed_at?->getTimestamp() ?? 0)
            ->first();
        $reviewedByName = optional($latestReviewed?->reviewer)->name;
        $latestReviewNote = $reviews
            ->filter(fn (TransactionRiskReview $review) => filled($review->review_note))
            ->sortByDesc(fn (TransactionRiskReview $review) => $review->reviewed_at?->getTimestamp() ?? $review->updated_at?->getTimestamp() ?? 0)
            ->first()?->review_note;

        return [
            'risk_level' => $riskLevel,
            'risk_score' => (int) $reviews->max('risk_score'),
            'review_status' => $reviewStatus,
            'review_note' => $latestReviewNote,
            'reasons' => $reviews->pluck('reasons')->flatten(1)->filter()->unique()->values()->all(),
            'duplicate_candidates' => $reviews->pluck('duplicate_candidates')->flatten(1)->filter()->unique(fn ($candidate) => ($candidate['transaction_id'] ?? '') . ':' . ($candidate['match_type'] ?? ''))->values()->all(),
            'summary_text' => $this->summaryText($riskLevel),
            'reviewed_by_name' => $reviewedByName,
            'reviewed_at' => $reviewedAt ? Carbon::parse($reviewedAt) : null,
        ];
    }

    public function groupReviewSummaries(Collection $groupNos): array
    {
        if ($groupNos->isEmpty()) {
            return [];
        }

        return $this->historySummarySubquery()
            ->whereIn('transaction_risk_reviews.group_no_transaksi', $groupNos->all())
            ->get()
            ->mapWithKeys(function ($review) {
                return [
                    $review->group_no_transaksi => [
                        'risk_level' => $this->riskLevelFromSeverity((int) $review->risk_severity),
                        'risk_score' => (int) ($review->risk_score_max ?? 0),
                        'review_status' => $this->reviewStatusFromSeverity((int) $review->review_severity),
                    ],
                ];
            })
            ->all();
    }

    public function warningGroupCount(?int $year = null, ?int $periodId = null, ?string $metode = null): int
    {
        return $this->activeReviewsQuery()
            ->where('transaction_risk_reviews.risk_level', TransactionRiskReview::LEVEL_WARNING)
            ->where('transaction_risk_reviews.review_status', '!=', TransactionRiskReview::REVIEW_AMAN)
            ->when($year, fn ($query) => $query->where('zakat_transactions.tahun_zakat', $year))
            ->when($periodId, fn ($query) => $query->where('zakat_transactions.zakat_period_id', $periodId))
            ->when($metode, fn ($query) => $query->where('zakat_transactions.metode', $metode))
            ->distinct()
            ->count('transaction_risk_reviews.group_no_transaksi');
    }

    public function sqlReviewSeverity(?string $status): int
    {
        return match ($status) {
            TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT => 3,
            TransactionRiskReview::REVIEW_AMAN => 2,
            TransactionRiskReview::REVIEW_BELUM_DITINJAU => 1,
            default => 0,
        };
    }

    public function sqlRiskSeverity(?string $level): int
    {
        return match ($level) {
            TransactionRiskReview::LEVEL_WARNING => 2,
            TransactionRiskReview::LEVEL_NORMAL => 1,
            default => 0,
        };
    }

    private function aggregateRiskLevel(EloquentCollection $reviews): string
    {
        $severity = $reviews->max(fn (TransactionRiskReview $review) => $this->sqlRiskSeverity($review->risk_level));
        return $this->riskLevelFromSeverity((int) $severity);
    }

    private function aggregateReviewStatus(EloquentCollection $reviews): string
    {
        $hasNeedFollowUp = $reviews->contains(fn (TransactionRiskReview $review) => $review->review_status === TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT);
        if ($hasNeedFollowUp) {
            return TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT;
        }

        $allSafe = $reviews->every(fn (TransactionRiskReview $review) => $review->review_status === TransactionRiskReview::REVIEW_AMAN);
        if ($allSafe) {
            return TransactionRiskReview::REVIEW_AMAN;
        }

        return TransactionRiskReview::REVIEW_BELUM_DITINJAU;
    }

    private function riskLevelFromSeverity(int $severity): ?string
    {
        return match ($severity) {
            2 => TransactionRiskReview::LEVEL_WARNING,
            1 => TransactionRiskReview::LEVEL_NORMAL,
            default => null,
        };
    }

    private function reviewStatusFromSeverity(int $severity): ?string
    {
        return match ($severity) {
            3 => TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT,
            2 => TransactionRiskReview::REVIEW_AMAN,
            1 => TransactionRiskReview::REVIEW_BELUM_DITINJAU,
            default => null,
        };
    }

    private function summaryText(?string $riskLevel): string
    {
        return match ($riskLevel) {
            TransactionRiskReview::LEVEL_WARNING => 'Transaksi ini perlu dicek ulang sebelum ditutup aman atau diteruskan ke tindak lanjut.',
            TransactionRiskReview::LEVEL_NORMAL => 'Tidak ada sinyal risiko utama yang terdeteksi pada transaksi ini.',
            default => 'Belum ada hasil analisis risiko untuk transaksi ini.',
        };
    }

    private function reviewSeveritySummarySql(): array
    {
        return [
            'CASE ' .
            'WHEN MAX(CASE WHEN review_status = ? THEN 1 ELSE 0 END) = 1 THEN 3 ' .
            'WHEN MIN(CASE WHEN review_status = ? THEN 1 ELSE 0 END) = 1 THEN 2 ' .
            'ELSE 1 END as review_severity',
            [
                TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT,
                TransactionRiskReview::REVIEW_AMAN,
            ],
        ];
    }

    public function activeReviewsQuery(): Builder
    {
        return TransactionRiskReview::query()
            ->join('zakat_transactions', 'zakat_transactions.id', '=', 'transaction_risk_reviews.zakat_transaction_id')
            ->whereNull('zakat_transactions.deleted_at');
    }
}

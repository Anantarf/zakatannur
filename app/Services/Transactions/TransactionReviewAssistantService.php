<?php

namespace App\Services\Transactions;

use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

            TransactionRiskReview::query()->updateOrCreate(
                ['zakat_transaction_id' => $transaction->id],
                [
                    'group_no_transaksi' => $transaction->no_transaksi,
                    'risk_level' => $analysis['risk_level'],
                    'risk_score' => $analysis['risk_score'],
                    'risk_flags' => $analysis['risk_flags'],
                    'reasons' => $analysis['reasons'],
                    'duplicate_candidates' => $analysis['duplicate_candidates'],
                    'detector_version' => $analysis['detector_version'],
                    'review_status' => TransactionRiskReview::REVIEW_BELUM_DITINJAU,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'checked_at' => now(config('zakat.timezone')),
                ]
            );
        }
    }

    public function attachHistorySummaries($paginator): void
    {
        $groupNos = collect($paginator->items())->pluck('no_transaksi')->filter()->values();
        $summaries = $this->summariesForGroups($groupNos);

        $paginator->getCollection()->transform(function ($transaction) use ($summaries) {
            $summary = $summaries[$transaction->no_transaksi] ?? null;
            $transaction->risk_level = $summary['risk_level'] ?? null;
            $transaction->review_status = $summary['review_status'] ?? null;
            return $transaction;
        });
    }

    public function detailReviewForGroup(string $noTransaksi): array
    {
        $reviews = TransactionRiskReview::query()
            ->with(['reviewer', 'transaction.muzakki' => fn ($query) => $query->withTrashed()])
            ->where('group_no_transaksi', $noTransaksi)
            ->whereHas('transaction', fn ($query) => $query->whereNull('deleted_at'))
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'risk_level' => null,
                'risk_score' => 0,
                'review_status' => null,
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
        $reviewedByName = optional($reviews->sortByDesc('reviewed_at')->firstWhere('reviewed_at', '!=', null)?->reviewer)->name;

        return [
            'risk_level' => $riskLevel,
            'risk_score' => (int) $reviews->max('risk_score'),
            'review_status' => $reviewStatus,
            'reasons' => $reviews->pluck('reasons')->flatten(1)->filter()->unique()->values()->all(),
            'duplicate_candidates' => $reviews->pluck('duplicate_candidates')->flatten(1)->filter()->unique(fn ($candidate) => ($candidate['transaction_id'] ?? '') . ':' . ($candidate['match_type'] ?? ''))->values()->all(),
            'summary_text' => $this->summaryText($riskLevel),
            'reviewed_by_name' => $reviewedByName,
            'reviewed_at' => $reviewedAt ? Carbon::parse($reviewedAt) : null,
        ];
    }

    public function reviewPayloadForTransaction(int $transactionId): array
    {
        $transaction = ZakatTransaction::query()->findOrFail($transactionId);

        return $this->detailReviewForGroup($transaction->no_transaksi);
    }

    public function updateGroupReviewStatus(string $noTransaksi, string $status, int $reviewerId): void
    {
        TransactionRiskReview::query()
            ->where('group_no_transaksi', $noTransaksi)
            ->update([
                'review_status' => $status,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(config('zakat.timezone')),
            ]);
    }

    public function historySummarySubquery()
    {
        return TransactionRiskReview::query()
            ->selectRaw('group_no_transaksi')
            ->selectRaw('MAX(CASE risk_level WHEN ? THEN 3 WHEN ? THEN 2 ELSE 1 END) as risk_severity', [
                TransactionRiskReview::LEVEL_SUSPICIOUS,
                TransactionRiskReview::LEVEL_WARNING,
            ])
            ->selectRaw('MAX(CASE review_status WHEN ? THEN 3 WHEN ? THEN 2 ELSE 1 END) as review_severity', [
                TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT,
                TransactionRiskReview::REVIEW_AMAN,
            ])
            ->groupBy('group_no_transaksi');
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
            TransactionRiskReview::LEVEL_SUSPICIOUS => 3,
            TransactionRiskReview::LEVEL_WARNING => 2,
            TransactionRiskReview::LEVEL_NORMAL => 1,
            default => 0,
        };
    }

    private function summariesForGroups(Collection $groupNos): array
    {
        if ($groupNos->isEmpty()) {
            return [];
        }

        return TransactionRiskReview::query()
            ->select('group_no_transaksi')
            ->selectRaw('MAX(CASE risk_level WHEN ? THEN 3 WHEN ? THEN 2 ELSE 1 END) as risk_severity', [
                TransactionRiskReview::LEVEL_SUSPICIOUS,
                TransactionRiskReview::LEVEL_WARNING,
            ])
            ->selectRaw('MAX(CASE review_status WHEN ? THEN 3 WHEN ? THEN 2 ELSE 1 END) as review_severity', [
                TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT,
                TransactionRiskReview::REVIEW_AMAN,
            ])
            ->whereIn('group_no_transaksi', $groupNos->all())
            ->groupBy('group_no_transaksi')
            ->get()
            ->mapWithKeys(function (TransactionRiskReview $review) {
                return [
                    $review->group_no_transaksi => [
                        'risk_level' => $this->riskLevelFromSeverity((int) $review->risk_severity),
                        'review_status' => $this->reviewStatusFromSeverity((int) $review->review_severity),
                    ],
                ];
            })
            ->all();
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
            3 => TransactionRiskReview::LEVEL_SUSPICIOUS,
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
            TransactionRiskReview::LEVEL_SUSPICIOUS => 'Kandidat duplikasi atau anomali kuat ditemukan. Transaksi ini perlu perhatian operator.',
            TransactionRiskReview::LEVEL_WARNING => 'Ada sinyal risiko yang perlu dicek ulang secara manual.',
            TransactionRiskReview::LEVEL_NORMAL => 'Tidak ada sinyal risiko utama yang terdeteksi pada transaksi ini.',
            default => 'Belum ada hasil analisis risiko untuk transaksi ini.',
        };
    }
}

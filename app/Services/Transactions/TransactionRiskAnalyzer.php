<?php

namespace App\Services\Transactions;

use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;

class TransactionRiskAnalyzer
{
    public function __construct(
        private DuplicateTransactionDetector $duplicateDetector,
        private TransactionAnomalyDetector $anomalyDetector,
    ) {
    }

    public function analyze(ZakatTransaction $transaction): array
    {
        $duplicateResult = $this->duplicateDetector->analyze($transaction);
        $anomalyResult = $this->anomalyDetector->analyze($transaction);

        $score = (int) $duplicateResult['score'] + (int) $anomalyResult['score'];
        $flags = array_values(array_unique(array_merge($duplicateResult['flags'], $anomalyResult['flags'])));
        $reasons = array_values(array_unique(array_merge($duplicateResult['reasons'], $anomalyResult['reasons'])));
        $duplicateCandidates = $duplicateResult['candidates'];

        return [
            'risk_level' => $this->levelFromScore($score),
            'risk_score' => $score,
            'risk_flags' => $flags,
            'reasons' => $reasons,
            'duplicate_candidates' => $duplicateCandidates,
            'detector_version' => 'v1',
        ];
    }

    private function levelFromScore(int $score): string
    {
        if ($score >= 60) {
            return TransactionRiskReview::LEVEL_SUSPICIOUS;
        }

        if ($score >= 20) {
            return TransactionRiskReview::LEVEL_WARNING;
        }

        return TransactionRiskReview::LEVEL_NORMAL;
    }
}

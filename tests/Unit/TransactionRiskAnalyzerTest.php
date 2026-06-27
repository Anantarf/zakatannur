<?php

namespace Tests\Unit;

use App\Models\TransactionRiskReview;
use App\Models\ZakatTransaction;
use App\Services\Transactions\DuplicateTransactionDetector;
use App\Services\Transactions\TransactionAnomalyDetector;
use App\Services\Transactions\TransactionRiskAnalyzer;
use Tests\TestCase;

class TransactionRiskAnalyzerTest extends TestCase
{
    private function makeAnalyzer(array $duplicateResult, array $anomalyResult): TransactionRiskAnalyzer
    {
        $dupDetector = $this->createMock(DuplicateTransactionDetector::class);
        $dupDetector->method('analyze')->willReturn($duplicateResult);

        $anomalyDetector = $this->createMock(TransactionAnomalyDetector::class);
        $anomalyDetector->method('analyze')->willReturn($anomalyResult);

        return new TransactionRiskAnalyzer($dupDetector, $anomalyDetector);
    }

    private function emptyResult(int $score = 0): array
    {
        return ['score' => $score, 'flags' => [], 'reasons' => [], 'candidates' => []];
    }

    private function makeTx(): ZakatTransaction
    {
        return new ZakatTransaction();
    }

    public function test_zero_score_returns_level_normal(): void
    {
        $analyzer = $this->makeAnalyzer($this->emptyResult(), $this->emptyResult());

        $result = $analyzer->analyze($this->makeTx());

        $this->assertSame(TransactionRiskReview::LEVEL_SAFE, $result['risk_level']);
        $this->assertSame(0, $result['risk_score']);
    }

    public function test_score_19_stays_normal(): void
    {
        $analyzer = $this->makeAnalyzer($this->emptyResult(10), $this->emptyResult(9));

        $result = $analyzer->analyze($this->makeTx());

        $this->assertSame(TransactionRiskReview::LEVEL_SAFE, $result['risk_level']);
        $this->assertSame(19, $result['risk_score']);
    }

    public function test_score_20_becomes_warning(): void
    {
        $analyzer = $this->makeAnalyzer($this->emptyResult(20), $this->emptyResult());

        $result = $analyzer->analyze($this->makeTx());

        $this->assertSame(TransactionRiskReview::LEVEL_WARNING, $result['risk_level']);
        $this->assertSame(20, $result['risk_score']);
    }

    public function test_scores_from_both_detectors_are_summed(): void
    {
        $analyzer = $this->makeAnalyzer($this->emptyResult(15), $this->emptyResult(15));

        $result = $analyzer->analyze($this->makeTx());

        $this->assertSame(30, $result['risk_score']);
        $this->assertSame(TransactionRiskReview::LEVEL_WARNING, $result['risk_level']);
    }

    public function test_flags_from_both_detectors_are_merged_and_deduplicated(): void
    {
        $dupResult = array_merge($this->emptyResult(), [
            'flags' => ['exact_duplicate', 'shared_flag'],
            'reasons' => ['Duplikat ditemukan.'],
        ]);
        $anomalyResult = array_merge($this->emptyResult(), [
            'flags' => ['shared_flag', 'updated_after_receipt_printed'],
            'reasons' => ['Diubah setelah cetak.'],
        ]);

        $analyzer = $this->makeAnalyzer($dupResult, $anomalyResult);

        $result = $analyzer->analyze($this->makeTx());

        $this->assertCount(3, $result['risk_flags']);
        $this->assertContains('exact_duplicate', $result['risk_flags']);
        $this->assertContains('shared_flag', $result['risk_flags']);
        $this->assertContains('updated_after_receipt_printed', $result['risk_flags']);
    }

    public function test_reasons_from_both_detectors_are_deduplicated(): void
    {
        $shared = 'Alasan yang sama.';
        $dupResult = array_merge($this->emptyResult(), ['flags' => ['f1'], 'reasons' => [$shared, 'Alasan A.']]);
        $anomalyResult = array_merge($this->emptyResult(), ['flags' => ['f2'], 'reasons' => [$shared, 'Alasan B.']]);

        $analyzer = $this->makeAnalyzer($dupResult, $anomalyResult);

        $result = $analyzer->analyze($this->makeTx());

        $this->assertCount(3, $result['reasons']);
        $this->assertCount(count(array_unique($result['reasons'])), $result['reasons']);
    }

    public function test_duplicate_candidates_come_from_dup_detector(): void
    {
        $candidates = [['transaction_id' => 99, 'no_transaksi' => 'TRX-X', 'match_type' => 'exact_duplicate']];
        $dupResult = array_merge($this->emptyResult(60), ['flags' => ['exact_duplicate'], 'reasons' => ['Duplikat.'], 'candidates' => $candidates]);

        $analyzer = $this->makeAnalyzer($dupResult, $this->emptyResult());

        $result = $analyzer->analyze($this->makeTx());

        $this->assertSame($candidates, $result['duplicate_candidates']);
    }

    public function test_result_always_contains_expected_keys(): void
    {
        $analyzer = $this->makeAnalyzer($this->emptyResult(), $this->emptyResult());

        $result = $analyzer->analyze($this->makeTx());

        foreach (['risk_level', 'risk_score', 'risk_flags', 'reasons', 'duplicate_candidates', 'detector_version'] as $key) {
            $this->assertArrayHasKey($key, $result, "Missing key: {$key}");
        }
        $this->assertSame('v1', $result['detector_version']);
    }
}

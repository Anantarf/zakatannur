<?php

namespace Tests\Unit;

use App\Models\Muzakki;
use App\Models\TransactionRiskReview;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Services\Transactions\TransactionAnomalyDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TransactionAnomalyDetectorTest extends TestCase
{
    use RefreshDatabase;

    private TransactionAnomalyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new TransactionAnomalyDetector();
    }

    private function makeTx(array $attrs = [], array $context = []): ZakatTransaction
    {
        $tx = new ZakatTransaction(array_merge([
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 100_000,
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
        ], $attrs));

        // anomaly_context is a transient attribute set via setAttribute, not fillable
        if (!empty($context)) {
            $tx->setAttribute('anomaly_context', $context);
        }

        return $tx;
    }

    public function test_no_context_returns_zero_score(): void
    {
        $result = $this->detector->analyze($this->makeTx());

        $this->assertSame(0, $result['score']);
        $this->assertEmpty($result['flags']);
        $this->assertEmpty($result['reasons']);
    }

    public function test_restored_after_delete_adds_correct_score(): void
    {
        $tx = $this->makeTx([], ['restored_after_delete' => true]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(TransactionRiskReview::ANOMALY_FLAG_RESTORE_SCORE, $result['score']);
        $this->assertContains(TransactionRiskReview::FLAG_RESTORED_AFTER_DELETE, $result['flags']);
        $this->assertCount(1, $result['reasons']);
    }

    public function test_updated_after_receipt_printed_adds_30(): void
    {
        $tx = $this->makeTx([], ['updated_after_receipt_printed' => true]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(30, $result['score']);
        $this->assertContains('updated_after_receipt_printed', $result['flags']);
    }

    public function test_significant_nominal_change_adds_35_with_money_reason(): void
    {
        $tx = $this->makeTx([], [
            'significant_nominal_change' => true,
            'old_total_uang' => 100_000,
            'new_total_uang' => 500_000,
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(35, $result['score']);
        $this->assertContains('significant_nominal_change', $result['flags']);
        $this->assertStringContainsString('500000', $result['reasons'][0]);
    }

    public function test_significant_beras_change_uses_kg_in_reason(): void
    {
        $tx = $this->makeTx([
            'metode' => ZakatTransaction::METHOD_BERAS,
            'jumlah_beras_kg' => 5.0,
        ], [
            'significant_nominal_change' => true,
            'old_total_beras' => 2.5,
            'new_total_beras' => 5.0,
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(35, $result['score']);
        $this->assertStringContainsString('kg', $result['reasons'][0]);
    }

    public function test_multiple_flags_accumulate_score(): void
    {
        $tx = $this->makeTx([], [
            'restored_after_delete' => true,
            'updated_after_receipt_printed' => true,
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(TransactionRiskReview::ANOMALY_FLAG_RESTORE_SCORE + 30, $result['score']);
        $this->assertCount(2, $result['flags']);
    }

    public function test_beras_transaction_skipped_for_outlier_check(): void
    {
        $tx = $this->makeTx([
            'metode' => ZakatTransaction::METHOD_BERAS,
            'nominal_uang' => 0,
            'jumlah_beras_kg' => 9999.0,
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertNotContains('statistical_outlier', $result['flags']);
    }

    public function test_outlier_not_flagged_when_sample_below_minimum(): void
    {
        // Fewer than 10 rows means no baseline — outlier check skipped
        $tx = $this->makeTx(['nominal_uang' => 99_999_999]);

        $result = $this->detector->analyze($tx);

        $this->assertNotContains('statistical_outlier', $result['flags']);
    }

    public function test_statistical_outlier_flagged_when_nominal_exceeds_5x_average(): void
    {
        $muzakki = Muzakki::query()->create(['name' => 'Seeder']);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        for ($i = 0; $i < 10; $i++) {
            ZakatTransaction::query()->create([
                'no_transaksi' => "SEED-{$i}",
                'muzakki_id' => $muzakki->id,
                'pembayar_nama' => 'Test',
                'pembayar_phone' => '08123',
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFT_PAGI,
                'category' => ZakatTransaction::CATEGORY_MAL,
                'tahun_zakat' => 2026,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => 100_000,
                'petugas_id' => $staff->id,
                'status' => ZakatTransaction::STATUS_VALID,
            ]);
        }

        Cache::flush(); // force fresh average calculation

        $tx = $this->makeTx([
            'category' => ZakatTransaction::CATEGORY_MAL,
            'nominal_uang' => 600_000,
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertContains('statistical_outlier', $result['flags']);
        $this->assertSame(15, $result['score']);
        $this->assertStringContainsString('600.000', $result['reasons'][0]);
    }

    public function test_nominal_within_5x_average_not_flagged(): void
    {
        $muzakki = Muzakki::query()->create(['name' => 'Seeder']);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        for ($i = 0; $i < 10; $i++) {
            ZakatTransaction::query()->create([
                'no_transaksi' => "SEED-{$i}",
                'muzakki_id' => $muzakki->id,
                'pembayar_nama' => 'Test',
                'pembayar_phone' => '08123',
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFT_PAGI,
                'category' => ZakatTransaction::CATEGORY_MAL,
                'tahun_zakat' => 2026,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => 100_000,
                'petugas_id' => $staff->id,
                'status' => ZakatTransaction::STATUS_VALID,
            ]);
        }

        Cache::flush();

        $tx = $this->makeTx([
            'category' => ZakatTransaction::CATEGORY_MAL,
            'nominal_uang' => 499_000,
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertNotContains('statistical_outlier', $result['flags']);
    }
}

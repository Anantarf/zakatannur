<?php

namespace Tests\Feature;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\TransactionRiskReview;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRiskReviewBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_command_creates_missing_risk_reviews_without_overwriting_existing_ones(): void
    {
        $this->seedRiskDefaults();
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);

        $needsBackfill = $this->makeTransaction('TRX-20260517-0001', 'Pembayar Backfill', $petugas->id);
        $alreadyReviewed = $this->makeTransaction('TRX-20260517-0002', 'Pembayar Reviewed', $petugas->id);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $alreadyReviewed->id,
            'group_no_transaksi' => $alreadyReviewed->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_WARNING,
            'risk_score' => 25,
            'risk_flags' => ['updated_after_receipt_printed'],
            'reasons' => ['Transaksi diubah setelah kwitansi pernah dicetak dan perlu verifikasi ulang.'],
            'duplicate_candidates' => [],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_AMAN,
            'reviewed_by' => $petugas->id,
            'reviewed_at' => now(config('zakat.timezone')),
            'checked_at' => now(config('zakat.timezone')),
        ]);

        $this->artisan('transactions:backfill-risk-reviews', ['--chunk' => 1])
            ->expectsOutput('Memulai backfill review risiko untuk 1 transaksi aktif...')
            ->expectsOutput('Backfill selesai. 1 transaksi diproses.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('transaction_risk_reviews', [
            'zakat_transaction_id' => $needsBackfill->id,
            'group_no_transaksi' => $needsBackfill->no_transaksi,
        ]);

        $this->assertDatabaseHas('transaction_risk_reviews', [
            'zakat_transaction_id' => $alreadyReviewed->id,
            'review_status' => TransactionRiskReview::REVIEW_AMAN,
            'reviewed_by' => $petugas->id,
        ]);

        $this->assertSame(2, TransactionRiskReview::query()->count());
    }

    public function test_backfill_command_reports_when_nothing_needs_processing(): void
    {
        $this->artisan('transactions:backfill-risk-reviews')
            ->expectsOutput('Tidak ada transaksi aktif yang perlu dibackfill.')
            ->assertExitCode(0);
    }

    private function seedRiskDefaults(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 50000,
            'default_fidyah_beras_per_hari' => 0.75,
        ]);
    }

    private function makeTransaction(string $noTransaksi, string $payer, int $petugasId): ZakatTransaction
    {
        $muzakki = Muzakki::query()->create(['name' => $payer . ' Muzakki']);

        return ZakatTransaction::query()->create([
            'no_transaksi' => $noTransaksi,
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => $payer,
            'pembayar_phone' => '08123456789',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 50000,
            'jiwa' => 1,
            'petugas_id' => $petugasId,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
        ]);
    }
}

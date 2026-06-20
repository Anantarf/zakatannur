<?php

namespace Tests\Unit;

use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Services\Transactions\DuplicateTransactionDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateTransactionDetectorTest extends TestCase
{
    use RefreshDatabase;

    private DuplicateTransactionDetector $detector;
    private User $staff;
    private Muzakki $muzakki;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new DuplicateTransactionDetector();
        $this->staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $this->muzakki = Muzakki::query()->create(['name' => 'Ahmad']);
    }

    private function makeTx(array $overrides = []): ZakatTransaction
    {
        return ZakatTransaction::query()->create(array_merge([
            'no_transaksi' => 'TRX-' . uniqid(),
            'muzakki_id' => $this->muzakki->id,
            'pembayar_nama' => 'Ahmad Fauzi',
            'pembayar_phone' => '08123456789',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 100_000,
            'petugas_id' => $this->staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(),
        ], $overrides));
    }

    public function test_no_other_transactions_returns_zero_score(): void
    {
        $tx = $this->makeTx();

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
        $this->assertEmpty($result['flags']);
        $this->assertEmpty($result['candidates']);
    }

    public function test_exact_duplicate_within_window_scores_60(): void
    {
        $this->makeTx(['waktu_terima' => now()]);
        $tx = $this->makeTx(['waktu_terima' => now()->addMinutes(5)]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(60, $result['score']);
        $this->assertContains('exact_duplicate', $result['flags']);
        $this->assertCount(1, $result['candidates']);
        $this->assertSame('exact_duplicate', $result['candidates'][0]['match_type']);
    }

    public function test_transfer_duplicate_candidate_scores_50(): void
    {
        // Both transfers, same payer+amount, DIFFERENT muzakki — skips exact_duplicate branch
        $muzakki2 = Muzakki::query()->create(['name' => 'Budi']);
        $this->makeTx(['is_transfer' => true, 'muzakki_id' => $muzakki2->id]);
        $tx = $this->makeTx([
            'is_transfer' => true,
            'waktu_terima' => now()->addMinutes(5),
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertContains('transfer_duplicate_candidate', $result['flags']);
        $this->assertSame(50, $result['score']);
    }

    public function test_different_tahun_zakat_not_flagged(): void
    {
        $this->makeTx(['tahun_zakat' => 2025]);
        $tx = $this->makeTx(['tahun_zakat' => 2026]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
    }

    public function test_different_category_not_flagged(): void
    {
        $this->makeTx(['category' => ZakatTransaction::CATEGORY_MAL]);
        $tx = $this->makeTx(['category' => ZakatTransaction::CATEGORY_FITRAH]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
    }

    public function test_transaction_outside_30_min_window_not_flagged(): void
    {
        $this->makeTx(['waktu_terima' => now()->subMinutes(31)]);
        $tx = $this->makeTx(['waktu_terima' => now()]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
    }

    public function test_different_payer_name_not_flagged(): void
    {
        $this->makeTx(['pembayar_nama' => 'Budi Santoso', 'pembayar_phone' => '']);
        $tx = $this->makeTx(['pembayar_nama' => 'Ahmad Fauzi', 'pembayar_phone' => '']);

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
    }

    public function test_different_amount_not_flagged(): void
    {
        $this->makeTx(['nominal_uang' => 200_000]);
        $tx = $this->makeTx(['nominal_uang' => 100_000, 'waktu_terima' => now()->addMinutes(5)]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
    }

    public function test_same_transaction_group_excluded(): void
    {
        // Same no_transaksi = same group, should not count as duplicate
        $noTrx = 'TRX-SAME-GROUP';
        $this->makeTx(['no_transaksi' => $noTrx]);
        $tx = $this->makeTx(['no_transaksi' => $noTrx, 'waktu_terima' => now()->addMinutes(1)]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
    }

    public function test_beras_duplicate_uses_kg_comparison(): void
    {
        $this->makeTx([
            'metode' => ZakatTransaction::METHOD_BERAS,
            'nominal_uang' => 0,
            'jumlah_beras_kg' => 2.5,
        ]);
        $tx = $this->makeTx([
            'metode' => ZakatTransaction::METHOD_BERAS,
            'nominal_uang' => 0,
            'jumlah_beras_kg' => 2.5,
            'waktu_terima' => now()->addMinutes(5),
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(60, $result['score']);
        $this->assertContains('exact_duplicate', $result['flags']);
    }


    public function test_payer_match_same_beneficiary_when_phone_differs(): void
    {
        // Same payer name, different phone, same muzakki, same amount => payer_match_same_beneficiary (not exact_duplicate)
        $this->makeTx(['pembayar_nama' => 'Ahmad Fauzi', 'pembayar_phone' => '08111111111']);
        $tx = $this->makeTx([
            'pembayar_nama' => 'Ahmad Fauzi',
            'pembayar_phone' => '08222222222',
            'waktu_terima' => now()->addMinutes(5),
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertContains('payer_match_same_beneficiary', $result['flags']);
        $this->assertSame(40, $result['score']);
        $this->assertNotContains('exact_duplicate', $result['flags']);
    }

    public function test_exact_duplicate_takes_priority_when_both_phone_empty(): void
    {
        // When both phones are empty, name+amount match collapses to exact_duplicate (priority branch)
        $this->makeTx(['pembayar_nama' => 'Ahmad Fauzi', 'pembayar_phone' => '']);
        $tx = $this->makeTx([
            'pembayar_nama' => 'Ahmad Fauzi',
            'pembayar_phone' => '',
            'waktu_terima' => now()->addMinutes(5),
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertContains('exact_duplicate', $result['flags']);
        $this->assertSame(60, $result['score']);
        $this->assertNotContains('payer_match_same_beneficiary', $result['flags']);
    }

    public function test_payer_match_same_beneficiary_skips_when_names_differ(): void
    {
        // Phone empty, names differ => no flag (pembayar tidak cocok)
        $this->makeTx(['pembayar_nama' => 'Ahmad Fauzi', 'pembayar_phone' => '']);
        $tx = $this->makeTx([
            'pembayar_nama' => 'Budi Santoso',
            'pembayar_phone' => '',
            'waktu_terima' => now()->addMinutes(5),
        ]);

        $result = $this->detector->analyze($tx);

        $this->assertSame(0, $result['score']);
        $this->assertEmpty($result['flags']);
    }

    public function test_payer_name_comparison_is_case_insensitive(): void
    {
        $this->makeTx(['pembayar_nama' => 'AHMAD FAUZI', 'pembayar_phone' => '']);
        $tx = $this->makeTx(['pembayar_nama' => 'ahmad fauzi', 'pembayar_phone' => '']);

        $result = $this->detector->analyze($tx);

        $this->assertGreaterThan(0, $result['score']);
    }
}


<?php

namespace Tests\Feature;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\TransactionRiskReview;
use App\Models\User;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRiskReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_transaction_creates_risk_review_record(): void
    {
        $staff = $this->seedDefaultsAndStaff();

        $response = $this->actingAs($staff)->post('/internal/transactions/store', $this->storePayload([
            'pembayar_nama' => 'Pembayar Baru',
            'muzakki_name' => 'Ahmad',
            'jiwa' => 2,
        ]));

        $response->assertRedirect();

        $transaction = ZakatTransaction::query()->latest('id')->firstOrFail();
        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->first();

        $this->assertNotNull($review);
        $this->assertSame(TransactionRiskReview::LEVEL_NORMAL, $review->risk_level);
        $this->assertSame(TransactionRiskReview::REVIEW_BELUM_DITINJAU, $review->review_status);
    }

    public function test_update_transaction_refreshes_risk_review_analysis(): void
    {
        $staff = $this->seedDefaultsAndStaff();
        $existingMuzakki = Muzakki::query()->create([
            'name' => 'Budi',
            'phone' => '0812',
            'address' => 'Jakarta',
        ]);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260516-0009',
            'muzakki_id' => $existingMuzakki->id,
            'pembayar_nama' => 'Pembayar Update',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 50000,
            'jiwa' => 1,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($staff)->post('/internal/transactions/store', $this->storePayload([
            'pembayar_nama' => 'Pembayar Awal',
            'muzakki_name' => 'Calon Update',
            'jiwa' => 1,
        ]));

        $transaction = ZakatTransaction::query()->latest('id')->firstOrFail();

        $payload = [
            'pembayar_nama' => 'Pembayar Update',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'tahun_zakat' => 2026,
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'items' => [
                [
                    'id' => $transaction->id,
                    'muzakki_name' => 'Budi',
                    'category' => ZakatTransaction::CATEGORY_FITRAH,
                    'metode' => ZakatTransaction::METHOD_UANG,
                    'jiwa' => 1,
                    'nominal_uang' => 50000,
                ],
            ],
        ];

        $this->actingAs($staff)
            ->patch('/internal/transactions/' . $transaction->id . '/update', $payload)
            ->assertRedirect();

        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(TransactionRiskReview::LEVEL_SUSPICIOUS, $review->risk_level);
        $this->assertContains('exact_duplicate', $review->risk_flags ?? []);
    }

    public function test_duplicate_detection_ignores_transactions_from_different_years(): void
    {
        $staff = $this->seedDefaultsAndStaff();
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad Tahun Lama']);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20250516-0001',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Pembayar Tahun',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2025,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 50000,
            'jiwa' => 1,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => Carbon::parse('2025-05-16 10:00:00', config('zakat.timezone')),
        ]);

        $this->actingAs($staff)->post('/internal/transactions/store', $this->storePayload([
            'pembayar_nama' => 'Pembayar Tahun',
            'muzakki_name' => 'Ahmad Tahun Baru',
            'jiwa' => 1,
        ]))->assertRedirect();

        $transaction = ZakatTransaction::query()->latest('id')->firstOrFail();
        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();

        $this->assertSame(2026, $transaction->tahun_zakat);
        $this->assertSame(TransactionRiskReview::LEVEL_NORMAL, $review->risk_level);
    }

    public function test_same_payer_different_beneficiary_is_not_marked_suspicious(): void
    {
        $staff = $this->seedDefaultsAndStaff();

        $this->actingAs($staff)->post('/internal/transactions/store', $this->storePayload([
            'pembayar_nama' => 'Satu Pembayar',
            'muzakki_name' => 'Beneficiary A',
            'jiwa' => 1,
        ]))->assertRedirect();

        $this->actingAs($staff)->post('/internal/transactions/store', $this->storePayload([
            'pembayar_nama' => 'Satu Pembayar',
            'muzakki_name' => 'Beneficiary B',
            'jiwa' => 1,
        ]))->assertRedirect();

        $transaction = ZakatTransaction::query()->latest('id')->firstOrFail();
        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();

        $this->assertNotSame(TransactionRiskReview::LEVEL_SUSPICIOUS, $review->risk_level);
    }

    public function test_update_after_receipt_printed_creates_warning_review(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $muzakki = Muzakki::query()->create(['name' => 'Muzakki Printed']);

        $transaction = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260518-2001',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Pembayar Printed',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 1000,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
            'receipt_printed_at' => now(config('zakat.timezone')),
            'receipt_printed_by' => $staff->id,
        ]);

        $this->actingAs($admin)
            ->patch('/internal/transactions/' . $transaction->id . '/update', [
                'pembayar_nama' => 'Pembayar Printed',
                'pembayar_phone' => '0812',
                'pembayar_alamat' => 'Jakarta',
                'tahun_zakat' => 2026,
                'shift' => ZakatTransaction::SHIFT_PAGI,
                'items' => [
                    [
                        'id' => $transaction->id,
                        'muzakki_name' => 'Muzakki Printed',
                        'category' => ZakatTransaction::CATEGORY_MAL,
                        'metode' => ZakatTransaction::METHOD_UANG,
                        'nominal_uang' => 2000,
                    ],
                ],
            ])
            ->assertRedirect();

        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(TransactionRiskReview::LEVEL_WARNING, $review->risk_level);
        $this->assertContains('updated_after_receipt_printed', $review->risk_flags ?? []);
    }

    public function test_restore_transaction_creates_warning_review(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Muzakki Restore']);

        $transaction = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260518-2002',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Pembayar Restore',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 100000,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($admin)
            ->post('/internal/transactions/' . $transaction->id . '/trash', [
                'deleted_reason' => 'Audit restore test',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post('/internal/transactions/' . $transaction->id . '/restore')
            ->assertRedirect();

        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(TransactionRiskReview::LEVEL_WARNING, $review->risk_level);
        $this->assertContains('restored_after_delete', $review->risk_flags ?? []);
    }

    public function test_significant_nominal_change_creates_warning_review(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $muzakki = Muzakki::query()->create(['name' => 'Muzakki Delta']);

        $transaction = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260518-2003',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Pembayar Delta',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 100000,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($admin)
            ->patch('/internal/transactions/' . $transaction->id . '/update', [
                'pembayar_nama' => 'Pembayar Delta',
                'pembayar_phone' => '0812',
                'pembayar_alamat' => 'Jakarta',
                'tahun_zakat' => 2026,
                'shift' => ZakatTransaction::SHIFT_PAGI,
                'items' => [
                    [
                        'id' => $transaction->id,
                        'muzakki_name' => 'Muzakki Delta',
                        'category' => ZakatTransaction::CATEGORY_MAL,
                        'metode' => ZakatTransaction::METHOD_UANG,
                        'nominal_uang' => 250000,
                    ],
                ],
            ])
            ->assertRedirect();

        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(TransactionRiskReview::LEVEL_WARNING, $review->risk_level);
        $this->assertContains('significant_nominal_change', $review->risk_flags ?? []);
    }

    public function test_infaq_outlier_creates_warning_when_history_is_sufficient(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        foreach (range(1, 10) as $index) {
            $muzakki = Muzakki::query()->create(['name' => 'Histori Infaq ' . $index]);
            ZakatTransaction::query()->create([
                'no_transaksi' => 'TRX-20260518-3' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'muzakki_id' => $muzakki->id,
                'pembayar_nama' => 'Pembayar Infaq ' . $index,
                'pembayar_phone' => '0812' . $index,
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFT_PAGI,
                'category' => ZakatTransaction::CATEGORY_INFAK,
                'tahun_zakat' => 2026,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => 50000,
                'petugas_id' => $staff->id,
                'status' => ZakatTransaction::STATUS_VALID,
                'waktu_terima' => now(config('zakat.timezone'))->subMinutes(20 + $index),
            ]);
        }

        $this->actingAs($staff)->post('/internal/transactions/store', [
            'muzakki_name' => 'Outlier Infaq',
            'muzakki_address' => 'Jl. Contoh',
            'muzakki_phone' => '08999',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Pembayar Outlier',
            'pembayar_phone' => '08129999',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_INFAK,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 5000000,
        ])->assertRedirect();

        $transaction = ZakatTransaction::query()->latest('id')->firstOrFail();
        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();

        $this->assertSame(TransactionRiskReview::LEVEL_WARNING, $review->risk_level);
        $this->assertContains('infaq_outlier', $review->risk_flags ?? []);
    }

    public function test_infaq_outlier_is_ignored_when_history_is_insufficient(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        foreach (range(1, 3) as $index) {
            $muzakki = Muzakki::query()->create(['name' => 'Mini Histori ' . $index]);
            ZakatTransaction::query()->create([
                'no_transaksi' => 'TRX-20260518-4' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'muzakki_id' => $muzakki->id,
                'pembayar_nama' => 'Pembayar Mini ' . $index,
                'pembayar_phone' => '0822' . $index,
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFT_PAGI,
                'category' => ZakatTransaction::CATEGORY_INFAK,
                'tahun_zakat' => 2026,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => 50000,
                'petugas_id' => $staff->id,
                'status' => ZakatTransaction::STATUS_VALID,
                'waktu_terima' => now(config('zakat.timezone'))->subMinutes(10 + $index),
            ]);
        }

        $this->actingAs($staff)->post('/internal/transactions/store', [
            'muzakki_name' => 'Outlier Mini',
            'muzakki_address' => 'Jl. Contoh',
            'muzakki_phone' => '08888',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Pembayar Mini Outlier',
            'pembayar_phone' => '08229999',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_INFAK,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 5000000,
        ])->assertRedirect();

        $transaction = ZakatTransaction::query()->latest('id')->firstOrFail();
        $review = TransactionRiskReview::query()->where('zakat_transaction_id', $transaction->id)->firstOrFail();

        $this->assertSame(TransactionRiskReview::LEVEL_NORMAL, $review->risk_level);
        $this->assertNotContains('infaq_outlier', $review->risk_flags ?? []);
    }

    public function test_history_shows_risk_badges_and_filters(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $txA = $this->makeTransaction('TRX-20260516-0001', 'Pembayar A', 100000, $admin->id);
        $txB = $this->makeTransaction('TRX-20260516-0002', 'Pembayar B', 100000, $admin->id);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $txA->id,
            'group_no_transaksi' => $txA->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_SUSPICIOUS,
            'risk_score' => 80,
            'risk_flags' => ['exact_duplicate'],
            'reasons' => ['Kandidat duplikasi kuat.'],
            'duplicate_candidates' => [],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT,
            'checked_at' => now(config('zakat.timezone')),
        ]);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $txB->id,
            'group_no_transaksi' => $txB->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_NORMAL,
            'risk_score' => 0,
            'risk_flags' => [],
            'reasons' => [],
            'duplicate_candidates' => [],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_AMAN,
            'checked_at' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($admin)
            ->get('/internal/history')
            ->assertOk()
            ->assertSee('Suspicious')
            ->assertSee('Tindak Lanjut');

        $this->actingAs($admin)
            ->get('/internal/history?risk_level=suspicious')
            ->assertOk()
            ->assertSee('Pembayar A')
            ->assertDontSee('Pembayar B');

        $this->actingAs($admin)
            ->get('/internal/history?review_status=aman')
            ->assertOk()
            ->assertSee('Pembayar B')
            ->assertDontSee('Pembayar A');
    }

    public function test_anomaly_detail_shows_review_data_and_allows_status_update(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $tx = $this->makeTransaction('TRX-20260516-0010', 'Pembayar Detail', 100000, $admin->id);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $tx->id,
            'group_no_transaksi' => $tx->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_WARNING,
            'risk_score' => 25,
            'risk_flags' => ['fitrah_cash_mismatch'],
            'reasons' => ['Nominal fitrah tidak sesuai standar 1 jiwa pada tahun 2026.'],
            'duplicate_candidates' => [
                [
                    'transaction_id' => 999,
                    'no_transaksi' => 'TRX-20260516-0099',
                    'pembayar_nama' => 'Pembayar Lama',
                    'muzakki_name' => 'Ahmad',
                    'match_type' => 'payer_match_same_beneficiary',
                    'time_diff_minutes' => 4,
                ],
            ],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_BELUM_DITINJAU,
            'checked_at' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($admin)
            ->get('/internal/anomalies/' . $tx->no_transaksi)
            ->assertOk()
            ->assertSee('Detail Review Anomali')
            ->assertSee('Nominal fitrah tidak sesuai standar 1 jiwa pada tahun 2026.')
            ->assertSee('TRX-20260516-0099');

        $this->actingAs($admin)
            ->patch('/internal/anomalies/' . $tx->no_transaksi . '/review-status', [
                'review_status' => TransactionRiskReview::REVIEW_AMAN,
            ])
            ->assertRedirect('/internal/anomalies/' . $tx->no_transaksi);

        $this->assertDatabaseHas('transaction_risk_reviews', [
            'zakat_transaction_id' => $tx->id,
            'review_status' => TransactionRiskReview::REVIEW_AMAN,
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_view_anomaly_index_and_filter_cases(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $txA = $this->makeTransaction('TRX-20260516-0101', 'Pembayar Anomali A', 100000, $admin->id);
        $txB = $this->makeTransaction('TRX-20260516-0102', 'Pembayar Anomali B', 100000, $admin->id);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $txA->id,
            'group_no_transaksi' => $txA->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_SUSPICIOUS,
            'risk_score' => 80,
            'risk_flags' => ['exact_duplicate'],
            'reasons' => ['Kandidat duplikasi kuat.'],
            'duplicate_candidates' => [],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_BELUM_DITINJAU,
            'checked_at' => now(config('zakat.timezone')),
        ]);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $txB->id,
            'group_no_transaksi' => $txB->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_WARNING,
            'risk_score' => 25,
            'risk_flags' => ['infaq_outlier'],
            'reasons' => ['Nominal infaq jauh dari pola biasa.'],
            'duplicate_candidates' => [],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_AMAN,
            'checked_at' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($admin)
            ->get('/internal/anomalies')
            ->assertOk()
            ->assertSee('Review Anomali')
            ->assertSee('Pembayar Anomali A')
            ->assertDontSee('Pembayar Anomali B')
            ->assertSee('Kasus Aktif')
            ->assertSee('Riwayat Review')
            ->assertSee('Potensi transaksi ganda');

        $this->actingAs($admin)
            ->get('/internal/anomalies?scope=archived')
            ->assertOk()
            ->assertSee('Riwayat Review')
            ->assertSee('Pembayar Anomali B')
            ->assertDontSee('Pembayar Anomali A');

        $this->actingAs($admin)
            ->get('/internal/anomalies?flag_type=exact_duplicate')
            ->assertOk()
            ->assertSee('Pembayar Anomali A')
            ->assertDontSee('Pembayar Anomali B');
    }

    public function test_guest_cannot_access_risk_review_endpoints(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $tx = $this->makeTransaction('TRX-20260516-0020', 'Pembayar Guard', 100000, $admin->id);

        $this->get('/internal/anomalies/' . $tx->no_transaksi)
            ->assertRedirect('/?login=true');

        $this->patch('/internal/anomalies/' . $tx->no_transaksi . '/review-status', [
            'review_status' => TransactionRiskReview::REVIEW_AMAN,
        ])->assertRedirect('/?login=true');
    }

    public function test_staff_cannot_access_risk_review_endpoints_or_ui(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $tx = $this->makeTransaction('TRX-20260516-0021', 'Pembayar Staff Review', 100000, $staff->id);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $tx->id,
            'group_no_transaksi' => $tx->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_WARNING,
            'risk_score' => 25,
            'risk_flags' => ['fitrah_cash_mismatch'],
            'reasons' => ['Butuh cek ulang.'],
            'duplicate_candidates' => [],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_BELUM_DITINJAU,
            'checked_at' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($staff)
            ->get('/internal/anomalies')
            ->assertForbidden();

        $this->actingAs($staff)
            ->get('/internal/anomalies/' . $tx->no_transaksi)
            ->assertForbidden();

        $this->actingAs($staff)
            ->patch('/internal/anomalies/' . $tx->no_transaksi . '/review-status', [
                'review_status' => TransactionRiskReview::REVIEW_PERLU_TINDAK_LANJUT,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->get('/internal/history')
            ->assertOk()
            ->assertDontSee('Suspicious')
            ->assertDontSee('Tindak Lanjut');

        $this->actingAs($staff)
            ->get('/internal/transactions/' . $tx->id)
            ->assertOk()
            ->assertDontSee('Review Risiko')
            ->assertDontSee('Butuh cek ulang.');
    }

    private function seedDefaultsAndStaff(): User
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 50000,
            'default_fidyah_beras_per_hari' => 0.75,
        ]);

        return User::factory()->create(['role' => User::ROLE_STAFF]);
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'muzakki_name' => 'Ahmad',
            'muzakki_address' => 'Jl. Contoh',
            'muzakki_phone' => '081234',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_UANG,
            'jiwa' => 1,
            'nominal_uang' => null,
            'jumlah_beras_kg' => null,
        ], $overrides);
    }

    private function makeTransaction(string $noTransaksi, string $payer, int $nominal, int $petugasId): ZakatTransaction
    {
        $muzakki = Muzakki::query()->create(['name' => $payer . ' Muzakki']);

        return ZakatTransaction::query()->create([
            'no_transaksi' => $noTransaksi,
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => $payer,
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => $nominal,
            'petugas_id' => $petugasId,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
        ]);
    }
}

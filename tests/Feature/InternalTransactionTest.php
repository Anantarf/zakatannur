<?php

namespace Tests\Feature;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_create_requires_auth(): void
    {
        $response = $this->get('/internal/transactions/create');
        $response->assertRedirect(route('home', ['login' => 'true']));
    }

    public function test_internal_create_forbidden_for_unallowed_role(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $response = $this->actingAs($user)->get('/internal/transactions/create');
        $response->assertForbidden();
    }

    public function test_staff_can_create_transaction_and_get_no_transaksi(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $payload = [
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
            'jiwa' => 2,
            'nominal_uang' => null,
            'jumlah_beras_kg' => null,
            'keterangan' => 'Test',
            // waktu_terima intentionally omitted
        ];

        $response = $this->actingAs($staff)->post('/internal/transactions/store', $payload);

        $this->assertStringContainsString('/internal/transactions/', $response->headers->get('Location'));

        $this->assertDatabaseCount('zakat_transactions', 1);

        $trx = ZakatTransaction::query()->first();
        $this->assertNotNull($trx);

        $this->assertSame(2026, $trx->tahun_zakat);
        $this->assertSame(ZakatTransaction::STATUS_VALID, $trx->status);
        $this->assertSame(100000, $trx->nominal_uang);

        $this->assertMatchesRegularExpression('/^TRX-\d{8}-\d{4}$/', $trx->no_transaksi);
    }

    public function test_fitrah_beras_auto_calculates_beras_from_jiwa(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $payload = [
            'muzakki_name' => 'Budi',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_BERAS,
            'jiwa' => 3,
            // jumlah_beras_kg intentionally omitted; should be computed.
        ];

        $response = $this->actingAs($staff)->from('/internal/transactions/create')->post('/internal/transactions/store', $payload);

        $this->assertStringContainsString('/internal/transactions/', $response->headers->get('Location'));

        $this->assertDatabaseCount('zakat_transactions', 1);

        $trx = ZakatTransaction::query()->first();
        $this->assertNotNull($trx);

        $this->assertSame(ZakatTransaction::METHOD_BERAS, $trx->metode);
        $this->assertSame(2026, $trx->tahun_zakat);
        $this->assertSame(ZakatTransaction::STATUS_VALID, $trx->status);

        $this->assertNull($trx->nominal_uang);
        $this->assertSame(7.5, (float) $trx->jumlah_beras_kg);
    }

    public function test_mal_requires_nominal_for_non_beras_method(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $payload = [
            'muzakki_name' => 'Ahmad',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_MAL,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => null,
        ];

        $response = $this->actingAs($staff)->from('/internal/transactions/create')->post('/internal/transactions/store', $payload);

        $response->assertRedirect('/internal/transactions/create');
        $response->assertSessionHasErrors(['nominal_uang']);
        $this->assertDatabaseCount('zakat_transactions', 0);
    }

    public function test_fitrah_requires_defaults_or_nominal_for_non_beras_method(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        // AnnualSetting exists but defaults are not set (0)
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 0,
            'default_fidyah_per_hari' => 0,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $payload = [
            'muzakki_name' => 'Ahmad',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_UANG,
            'jiwa' => 2,
            'nominal_uang' => null,
        ];

        $response = $this->actingAs($staff)->from('/internal/transactions/create')->post('/internal/transactions/store', $payload);

        $response->assertRedirect('/internal/transactions/create');
        $response->assertSessionHasErrors(['nominal_uang']);
        $this->assertDatabaseCount('zakat_transactions', 0);
    }

    public function test_transaction_rejects_non_active_year(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $payload = [
            'muzakki_name' => 'Ahmad',
            'tahun_zakat' => 2025,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_UANG,
            'jiwa' => 1,
            'nominal_uang' => 50000,
        ];

        $response = $this->actingAs($staff)->from('/internal/transactions/create')->post('/internal/transactions/store', $payload);

        $response->assertRedirect('/internal/transactions/create');
        $response->assertSessionHasErrors(['tahun_zakat']);
        $this->assertDatabaseCount('zakat_transactions', 0);
    }

    public function test_fitrah_cash_custom_nominal_is_allowed_and_marked_khusus(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $payload = [
            'muzakki_name' => 'Ahmad',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_UANG,
            'jiwa' => 2,
            'nominal_uang' => 90000,
        ];

        $response = $this->actingAs($staff)->from('/internal/transactions/create')->post('/internal/transactions/store', $payload);

        $this->assertStringContainsString('/internal/transactions/', $response->headers->get('Location'));
        $this->assertDatabaseCount('zakat_transactions', 1);

        $trx = ZakatTransaction::query()->firstOrFail();
        $this->assertSame(90000, $trx->nominal_uang);
        $this->assertTrue((bool) $trx->is_khusus);
    }

    public function test_fitrah_beras_custom_quantity_is_allowed_and_marked_khusus(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $payload = [
            'muzakki_name' => 'Ahmad',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_BERAS,
            'jiwa' => 2,
            'jumlah_beras_kg' => 4.75,
        ];

        $response = $this->actingAs($staff)->from('/internal/transactions/create')->post('/internal/transactions/store', $payload);

        $this->assertStringContainsString('/internal/transactions/', $response->headers->get('Location'));
        $this->assertDatabaseCount('zakat_transactions', 1);

        $trx = ZakatTransaction::query()->firstOrFail();
        $this->assertSame(4.75, (float) $trx->jumlah_beras_kg);
        $this->assertTrue((bool) $trx->is_khusus);
    }

    public function test_creating_transaction_restores_soft_deleted_muzakki_instead_of_creating_duplicate(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $muzakki = Muzakki::query()->create([
            'name' => 'Budi',
            'phone' => '08123456789',
            'address' => 'Alamat Lama',
        ]);
        $muzakki->delete();

        $payload = [
            'muzakki_name' => 'Budi',
            'muzakki_address' => 'Alamat Baru',
            'muzakki_phone' => '08123456789',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_UANG,
            'jiwa' => 1,
            'nominal_uang' => 50000,
            'jumlah_beras_kg' => null,
        ];

        $response = $this->actingAs($staff)->from('/internal/transactions/create')->post('/internal/transactions/store', $payload);
        $this->assertStringContainsString('/internal/transactions/', $response->headers->get('Location'));

        $this->assertSame(
            1,
            Muzakki::withTrashed()->where('name', 'Budi')->where('phone', '08123456789')->count()
        );

        $muzakki->refresh();
        $this->assertFalse($muzakki->trashed());
        $this->assertSame('Alamat Baru', $muzakki->address);

        $trx = ZakatTransaction::query()->first();
        $this->assertNotNull($trx);
        $this->assertSame((int) $muzakki->id, (int) $trx->muzakki_id);
    }

    public function test_creating_transaction_with_empty_phone_does_not_reuse_existing_muzakki_with_same_name(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        Muzakki::query()->create([
            'name' => 'Ahmad',
            'phone' => null,
            'address' => 'Alamat Lama',
        ]);

        $payload = [
            'muzakki_name' => 'Ahmad',
            'muzakki_address' => 'Alamat Baru',
            'muzakki_phone' => '',
            'tahun_zakat' => 2026,
            'pembayar_nama' => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => 'pagi',
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'metode' => ZakatTransaction::METHOD_UANG,
            'jiwa' => 1,
            'nominal_uang' => 50000,
            'jumlah_beras_kg' => null,
        ];

        $response = $this->actingAs($staff)
            ->from('/internal/transactions/create')
            ->post('/internal/transactions/store', $payload);
            
        $response->assertStatus(302);
        $this->assertStringContainsString('/internal/transactions/', $response->headers->get('Location'));

        $this->assertSame(2, Muzakki::query()->where('name', 'Ahmad')->count());
    }

    public function test_update_rejects_item_ids_from_other_transaction_groups(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakkiA = Muzakki::query()->create(['name' => 'Ahmad']);
        $muzakkiB = Muzakki::query()->create(['name' => 'Budi']);

        $mainTx = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260516-0001',
            'muzakki_id' => $muzakkiA->id,
            'pembayar_nama' => 'Pembayar A',
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

        $foreignTx = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260516-0002',
            'muzakki_id' => $muzakkiB->id,
            'pembayar_nama' => 'Pembayar B',
            'pembayar_phone' => '0813',
            'pembayar_alamat' => 'Bandung',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 200000,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
        ]);

        $payload = [
            'pembayar_nama' => 'Pembayar A',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'tahun_zakat' => 2026,
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'items' => [
                [
                    'id' => $foreignTx->id,
                    'muzakki_name' => 'Budi',
                    'category' => ZakatTransaction::CATEGORY_MAL,
                    'metode' => ZakatTransaction::METHOD_UANG,
                    'nominal_uang' => 250000,
                ],
            ],
        ];

        $this->actingAs($staff)
            ->from('/internal/transactions/' . $mainTx->id . '/edit')
            ->patch('/internal/transactions/' . $mainTx->id . '/update', $payload)
            ->assertRedirect('/internal/transactions/' . $mainTx->id . '/edit')
            ->assertSessionHasErrors(['items.0.id']);

        $foreignTx->refresh();
        $this->assertSame('TRX-20260516-0002', $foreignTx->no_transaksi);
        $this->assertSame(200000, $foreignTx->nominal_uang);
    }

    public function test_staff_cannot_update_transaction_after_receipt_is_printed(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Budi']);

        $trx = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260518-0001',
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

        $payload = [
            'pembayar_nama' => 'Pembayar Printed',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'tahun_zakat' => 2026,
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'items' => [
                [
                    'id' => $trx->id,
                    'muzakki_name' => 'Budi',
                    'category' => ZakatTransaction::CATEGORY_MAL,
                    'metode' => ZakatTransaction::METHOD_UANG,
                    'nominal_uang' => 2000,
                ],
            ],
        ];

        $this->actingAs($staff)
            ->patch('/internal/transactions/' . $trx->id . '/update', $payload)
            ->assertForbidden();
    }

    public function test_admin_can_update_transaction_after_receipt_is_printed(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fidyah_per_hari' => 50000,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $muzakki = Muzakki::query()->create(['name' => 'Budi']);

        $trx = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260518-0002',
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

        $payload = [
            'pembayar_nama' => 'Pembayar Printed',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'tahun_zakat' => 2026,
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'items' => [
                [
                    'id' => $trx->id,
                    'muzakki_name' => 'Budi',
                    'category' => ZakatTransaction::CATEGORY_MAL,
                    'metode' => ZakatTransaction::METHOD_UANG,
                    'nominal_uang' => 2000,
                ],
            ],
        ];

        $this->actingAs($admin)
            ->patch('/internal/transactions/' . $trx->id . '/update', $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('zakat_transactions', [
            'id' => $trx->id,
            'nominal_uang' => 2000,
        ]);
    }
}



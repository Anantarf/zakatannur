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
            // nominal_uang should be ignored for beras.
            'nominal_uang' => 12345,
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
}



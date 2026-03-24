<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHistoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_defaults_to_active_year_when_year_not_provided(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);

        $muzakki1 = Muzakki::query()->create(['name' => 'Ahmad']);
        $muzakki2 = Muzakki::query()->create(['name' => 'Budi']);

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0001',
            'muzakki_id'     => $muzakki1->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 1000,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20250308-0001',
            'muzakki_id'     => $muzakki2->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat'    => 2025,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 2000,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        $this->actingAs($staff)
            ->get('/internal/history')
            ->assertOk()
            ->assertSee('TRX-20260308-0001')
            ->assertDontSee('TRX-20250308-0001');
    }

    public function test_history_can_filter_by_category_metode_and_petugas(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $staff    = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugasA = User::factory()->create(['role' => User::ROLE_STAFF, 'name' => 'Petugas A']);
        $petugasB = User::factory()->create(['role' => User::ROLE_STAFF, 'name' => 'Petugas B']);
        $muzakki  = Muzakki::query()->create(['name' => 'Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi'    => 'TRX-20260308-0100',
            'muzakki_id'      => $muzakki->id,
            'pembayar_nama'   => 'Hamba Allah',
            'pembayar_phone'  => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift'           => ZakatTransaction::SHIFTS[0],
            'category'        => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'     => 2026,
            'metode'          => ZakatTransaction::METHOD_BERAS,
            'jumlah_beras_kg' => 2.5,
            'petugas_id'      => $petugasA->id,
            'status'          => ZakatTransaction::STATUS_VALID,
        ]);

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0200',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 50000,
            'petugas_id'     => $petugasA->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        ZakatTransaction::query()->create([
            'no_transaksi'    => 'TRX-20260308-0300',
            'muzakki_id'      => $muzakki->id,
            'pembayar_nama'   => 'Hamba Allah',
            'pembayar_phone'  => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift'           => ZakatTransaction::SHIFTS[0],
            'category'        => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'     => 2026,
            'metode'          => ZakatTransaction::METHOD_BERAS,
            'jumlah_beras_kg' => 5.0,
            'petugas_id'      => $petugasB->id,
            'status'          => ZakatTransaction::STATUS_VALID,
        ]);

        $this->actingAs($staff)
            ->get('/internal/history?year=2026&category=fitrah&metode=beras&petugas_id=' . $petugasA->id)
            ->assertOk()
            ->assertSee('TRX-20260308-0100')
            ->assertDontSee('TRX-20260308-0200')
            ->assertDontSee('TRX-20260308-0300');
    }

    public function test_history_search_matches_trashed_muzakki_phone(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);

        // Muzakki that will be soft-deleted; its phone should still be searchable
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad', 'phone' => '08123456789']);
        $muzakki->delete();

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0900',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 1000,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        // Unrelated transaction — must NOT appear in results
        $muzakkiOther = Muzakki::query()->create(['name' => 'Other Person', 'phone' => '09999999999']);
        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0901',
            'muzakki_id'     => $muzakkiOther->id,
            'pembayar_nama'  => 'Orang Lain',
            'pembayar_phone' => '0999',
            'pembayar_alamat'=> 'Bandung',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 5000,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        $this->actingAs($staff)
            ->get('/internal/history?q=081234')
            ->assertOk()
            ->assertSee('TRX-20260308-0900')
            ->assertDontSee('TRX-20260308-0901');
    }
}

<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatPeriod;
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

    public function test_history_grouped_transaction_keeps_multiple_categories_and_methods_visible(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakkiA = Muzakki::query()->create(['name' => 'Ahmad']);
        $muzakkiB = Muzakki::query()->create(['name' => 'Budi']);

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0999',
            'muzakki_id'     => $muzakkiA->id,
            'pembayar_nama'  => 'Pembayar Gabungan',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 10000,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        ZakatTransaction::query()->create([
            'no_transaksi'    => 'TRX-20260308-0999',
            'muzakki_id'      => $muzakkiB->id,
            'pembayar_nama'   => 'Pembayar Gabungan',
            'pembayar_phone'  => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift'           => ZakatTransaction::SHIFTS[0],
            'category'        => ZakatTransaction::CATEGORY_INFAK,
            'tahun_zakat'     => 2026,
            'metode'          => ZakatTransaction::METHOD_BERAS,
            'jumlah_beras_kg' => 2.5,
            'petugas_id'      => $petugas->id,
            'status'          => ZakatTransaction::STATUS_VALID,
        ]);

        $this->actingAs($staff)
            ->get('/internal/history?q=Pembayar%20Gabungan')
            ->assertOk()
            ->assertSee('TRX-20260308-0999')
            ->assertSee('Zakat Mal')
            ->assertSee('Infaq Shodaqoh')
            ->assertSee('Uang')
            ->assertSee('Beras');
    }

    public function test_history_can_filter_same_year_by_zakat_period(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2030']);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        $firstPeriod = ZakatPeriod::query()->create([
            'code' => 'ramadan-2030-1',
            'label' => 'Ramadan 1451 H',
            'gregorian_year' => 2030,
            'hijri_year' => 1451,
            'hijri_month' => 9,
            'sequence' => 1,
        ]);

        $secondPeriod = ZakatPeriod::query()->create([
            'code' => 'ramadan-2030-2',
            'label' => 'Ramadan 1452 H',
            'gregorian_year' => 2030,
            'hijri_year' => 1452,
            'hijri_month' => 9,
            'sequence' => 2,
        ]);

        foreach ([[$firstPeriod, 'TRX-20300105-0001'], [$secondPeriod, 'TRX-20301226-0001']] as [$period, $number]) {
            ZakatTransaction::query()->create([
                'no_transaksi' => $number,
                'muzakki_id' => $muzakki->id,
                'zakat_period_id' => $period->id,
                'hijri_year' => $period->hijri_year,
                'hijri_month' => $period->hijri_month,
                'pembayar_nama' => 'Hamba Allah',
                'pembayar_phone' => '0812',
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFTS[0],
                'category' => ZakatTransaction::CATEGORY_MAL,
                'tahun_zakat' => 2030,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => 1000,
                'petugas_id' => $petugas->id,
                'status' => ZakatTransaction::STATUS_VALID,
            ]);
        }

        $this->actingAs($staff)
            ->get('/internal/history?period_id=' . $secondPeriod->id)
            ->assertOk()
            ->assertSee('TRX-20301226-0001')
            ->assertDontSee('TRX-20300105-0001');
    }
}

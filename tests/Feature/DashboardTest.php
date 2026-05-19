<?php

namespace Tests\Feature;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatPeriod;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_dashboard_and_latest_transactions(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-7777',
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

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Tahun 2026')
            ->assertSee('TRX-20260308-7777');
    }

    public function test_dashboard_year_filter_changes_latest_transactions(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0001',
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

        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20250308-0001',
            'muzakki_id'     => $muzakki->id,
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
            ->get('/dashboard?year=2025')
            ->assertOk()
            ->assertSee('TRX-20250308-0001')
            ->assertDontSee('TRX-20260308-0001');
    }

    public function test_dashboard_chart_uses_selected_year_and_admin_chart_range(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2025,
            'chart_starts_at' => '2025-03-10',
            'chart_ends_at' => '2025-03-11',
        ]);
        AnnualSetting::query()->create([
            'year' => 2026,
            'chart_starts_at' => '2026-03-10',
            'chart_ends_at' => '2026-03-11',
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        foreach ([2025, 2026] as $year) {
            ZakatTransaction::query()->create([
                'no_transaksi' => "TRX-{$year}0310-0001",
                'muzakki_id' => $muzakki->id,
                'pembayar_nama' => 'Hamba Allah',
                'pembayar_phone' => '0812',
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFTS[0],
                'category' => ZakatTransaction::CATEGORY_MAL,
                'tahun_zakat' => $year,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => 1000,
                'petugas_id' => $petugas->id,
                'status' => ZakatTransaction::STATUS_VALID,
                'waktu_terima' => "{$year}-03-10 08:00:00",
            ]);
        }

        $this->actingAs($staff)
            ->get('/dashboard?year=2025')
            ->assertOk()
            ->assertSee('10 Mar - 11 Mar 2025')
            ->assertSee("TRX-20250310-0001")
            ->assertDontSee("TRX-20260310-0001");
    }

    public function test_dashboard_rekap_can_filter_same_year_by_zakat_period(): void
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

        foreach ([[$firstPeriod, 'TRX-20300105-0001', 1000], [$secondPeriod, 'TRX-20301226-0001', 2000]] as [$period, $number, $amount]) {
            ZakatTransaction::query()->create([
                'no_transaksi' => $number,
                'muzakki_id' => $muzakki->id,
                'zakat_period_id' => $period->id,
                'pembayar_nama' => 'Hamba Allah',
                'pembayar_phone' => '0812',
                'pembayar_alamat' => 'Jakarta',
                'shift' => ZakatTransaction::SHIFTS[0],
                'category' => ZakatTransaction::CATEGORY_MAL,
                'tahun_zakat' => 2030,
                'metode' => ZakatTransaction::METHOD_UANG,
                'nominal_uang' => $amount,
                'petugas_id' => $petugas->id,
                'status' => ZakatTransaction::STATUS_VALID,
            ]);
        }

        $this->actingAs($staff)
            ->get('/dashboard?period_id=' . $secondPeriod->id)
            ->assertOk()
            ->assertSee('TRX-20301226-0001')
            ->assertSee('Rp 2.000')
            ->assertDontSee('TRX-20300105-0001');
    }
}

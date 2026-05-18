<?php

namespace Tests\Feature;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_daily_rekap_file(): void
    {
        $admin = $this->seedExportFixtures(User::ROLE_ADMIN);

        $response = $this->actingAs($admin)
            ->get('/internal/rekap/export/daily?date=2026-05-16');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Rekap_Zakat_2026-05-16.xlsx', $response->headers->get('content-disposition', ''));
    }

    public function test_admin_can_export_yearly_rekap_file(): void
    {
        $admin = $this->seedExportFixtures(User::ROLE_ADMIN);

        $response = $this->actingAs($admin)
            ->get('/internal/rekap/export/yearly?year=2026');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Rekap_Tahunan_2026.xlsx', $response->headers->get('content-disposition', ''));
    }

    private function seedExportFixtures(string $role): User
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 50000,
            'default_fidyah_beras_per_hari' => 0.75,
        ]);

        $admin = User::factory()->create(['role' => $role]);
        $muzakki = Muzakki::query()->create(['name' => 'Export Ahmad']);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260516-0001',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Pembayar Export',
            'pembayar_phone' => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 50000,
            'jiwa' => 1,
            'petugas_id' => $admin->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone'))->setDate(2026, 5, 16)->setTime(9, 15),
        ]);

        return $admin;
    }
}

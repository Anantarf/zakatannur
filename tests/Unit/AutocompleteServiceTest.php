<?php

namespace Tests\Unit;

use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Services\AutocompleteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutocompleteServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeTx(string $pembayarNama, string $noTransaksi): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => $pembayarNama]);

        ZakatTransaction::query()->create([
            'no_transaksi'    => $noTransaksi,
            'muzakki_id'      => $muzakki->id,
            'pembayar_nama'   => $pembayarNama,
            'pembayar_phone'  => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift'           => ZakatTransaction::SHIFTS[0],
            'category'        => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'     => 2026,
            'metode'          => ZakatTransaction::METHOD_UANG,
            'nominal_uang'    => 50000,
            'petugas_id'      => $staff->id,
            'status'          => ZakatTransaction::STATUS_VALID,
            'waktu_terima'    => now(),
        ]);
    }

    public function test_get_autocomplete_data_returns_unique_pembayar_names(): void
    {
        $this->makeTx('Ahmad Hidayat', 'TRX-20260630-0001');
        $this->makeTx('Ahmad Hidayat', 'TRX-20260630-0002');
        $this->makeTx('Budi Santoso',  'TRX-20260630-0003');

        $data = AutocompleteService::getAutocompleteData(['pembayar_nama']);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('pembayar_nama', $data);
        $this->assertCount(2, $data['pembayar_nama']);
        $this->assertContains('Ahmad Hidayat', $data['pembayar_nama']);
        $this->assertContains('Budi Santoso', $data['pembayar_nama']);
    }

    public function test_get_autocomplete_data_with_no_types_returns_all(): void
    {
        $this->makeTx('Ahmad', 'TRX-20260630-0001');

        $data = AutocompleteService::getAutocompleteData([]);

        $this->assertArrayHasKey('pembayar_nama', $data);
        $this->assertArrayHasKey('category', $data);
        $this->assertArrayHasKey('no_transaksi', $data);
    }
}

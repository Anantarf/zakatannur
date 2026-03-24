<?php

namespace Tests\Feature;

use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSummaryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_summary_updated_at_wib_reflects_cached_compute_time(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 8, 10, 0, 0, 'Asia/Jakarta'));

        $first = $this->getJson('/api/public/summary?year=2026');
        $first->assertOk();
        $firstUpdated = (string) $first->json('updated_at_wib');

        Carbon::setTestNow(Carbon::create(2026, 3, 8, 10, 0, 10, 'Asia/Jakarta'));

        $second = $this->getJson('/api/public/summary?year=2026');
        $second->assertOk();
        $secondUpdated = (string) $second->json('updated_at_wib');

        // Same within TTL because it is pulled from cached payload.
        $this->assertSame($firstUpdated, $secondUpdated);

        Carbon::setTestNow();
    }

    public function test_public_summary_aggregates_only_valid_transactions(): void
    {
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create([
            'name'   => 'Ahmad',
            'address'=> 'Jl. Contoh',
            'phone'  => '081234',
        ]);

        // Valid fitrah cash
        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0001',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 10000,
            'jumlah_beras_kg'=> null,
            'jiwa'           => 1,
            'hari'           => null,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VALID,
            'waktu_terima'   => now('Asia/Jakarta'),
        ]);

        // Valid fitrah rice
        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0002',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_BERAS,
            'nominal_uang'   => null,
            'jumlah_beras_kg'=> 2.5,
            'jiwa'           => 1,
            'hari'           => null,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VALID,
            'waktu_terima'   => now('Asia/Jakarta'),
        ]);

        // Void transaction — must NOT be counted
        ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0003',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 9999,
            'jumlah_beras_kg'=> null,
            'jiwa'           => 1,
            'hari'           => null,
            'petugas_id'     => $petugas->id,
            'status'         => ZakatTransaction::STATUS_VOID,
            'void_reason'    => 'Salah input',
            'voided_at'      => now('Asia/Jakarta'),
            'voided_by'      => $petugas->id,
            'waktu_terima'   => now('Asia/Jakarta'),
        ]);

        $response = $this->getJson('/api/public/summary?year=2026');

        $response->assertOk();
        $response->assertJsonPath('data.year', 2026);

        $items  = collect($response->json('data.items'));
        $fitrah = $items->firstWhere('category', ZakatTransaction::CATEGORY_FITRAH);

        $this->assertNotNull($fitrah);
        $this->assertSame(10000, $fitrah['total_uang']);
        $this->assertSame(2.5,   $fitrah['total_beras_kg']);
        $this->assertSame(2,     $fitrah['jumlah_transaksi']);

        // Display strings should be consistent with numeric values
        $this->assertSame('Rp 10.000',             $fitrah['total_uang_display']);
        $this->assertSame('2,50 Kg',               $fitrah['total_beras_kg_display']);
        $this->assertSame('Rp 10.000 + 2,50 Kg',  $fitrah['total_display']);
    }
}

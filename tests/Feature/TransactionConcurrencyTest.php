<?php

namespace Tests\Feature;

use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use App\Services\Transactions\TransactionNumberGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequential_transactions_on_same_day_get_unique_numbers(): void
    {
        $generator = app(TransactionNumberGenerator::class);
        $day = Carbon::parse('2026-06-30 10:00:00');

        $firstNo  = $generator->generate($day);
        ZakatTransaction::query()->create($this->txData($firstNo, $day));

        $secondNo = $generator->generate($day);

        $this->assertNotEquals($firstNo, $secondNo, 'Dua transaksi di hari yang sama harus punya no_transaksi berbeda.');
        $this->assertStringEndsWith('0002', $secondNo);
    }

    public function test_no_transaksi_sequence_increments_correctly_across_multiple_creates(): void
    {
        $generator = app(TransactionNumberGenerator::class);
        $day = Carbon::parse('2026-06-30 10:00:00');
        $prefix = 'TRX-20260630-';

        foreach (range(1, 5) as $i) {
            $no = $generator->generate($day);
            $this->assertSame($prefix . str_pad((string) $i, 4, '0', STR_PAD_LEFT), $no);
            ZakatTransaction::query()->create($this->txData($no, $day));
        }

        $this->assertSame(5, ZakatTransaction::count());
        $this->assertSame(5, ZakatTransaction::distinct()->count('no_transaksi'));
    }

    public function test_generator_never_produces_duplicate_no_transaksi(): void
    {
        // ponytail: DB-level unique was intentionally dropped (migration 2026_03_17).
        // Uniqueness is enforced at application level via TransactionNumberGenerator.
        $generator = app(TransactionNumberGenerator::class);
        $day = Carbon::parse('2026-06-30 10:00:00');
        $generated = [];

        foreach (range(1, 10) as $i) {
            $no = $generator->generate($day);
            $this->assertNotContains($no, $generated, "Duplicate no_transaksi generated on iteration $i");
            $generated[] = $no;
            ZakatTransaction::query()->create($this->txData($no, $day));
        }

        $this->assertCount(10, array_unique($generated));
    }

    private function txData(string $noTransaksi, Carbon $waktu): array
    {
        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Test Muzakki']);

        return [
            'no_transaksi'    => $noTransaksi,
            'muzakki_id'      => $muzakki->id,
            'pembayar_nama'   => 'Hamba Allah',
            'pembayar_phone'  => '0812',
            'pembayar_alamat' => 'Jakarta',
            'shift'           => ZakatTransaction::SHIFTS[0],
            'category'        => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'     => $waktu->year,
            'metode'          => ZakatTransaction::METHOD_UANG,
            'nominal_uang'    => 50000,
            'petugas_id'      => $staff->id,
            'status'          => ZakatTransaction::STATUS_VALID,
            'waktu_terima'    => $waktu,
        ];
    }
}

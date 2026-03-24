<?php

namespace Tests\Feature;

use App\Models\Muzakki;
use App\Models\Template;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptPrintTest extends TestCase
{
    use RefreshDatabase;

    private function seedActiveLetterheadTemplate(): void
    {
        Storage::fake('local');

        $tcpdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $tcpdf->setPrintHeader(false);
        $tcpdf->setPrintFooter(false);
        $tcpdf->AddPage();
        $letterheadBytes = $tcpdf->Output('', 'S');

        $storagePath = 'templates/letterhead/test_letterhead.pdf';
        Storage::disk('local')->put($storagePath, $letterheadBytes);

        Template::query()->create([
            'template_type'    => Template::TYPE_LETTERHEAD,
            'version'          => 1,
            'is_active'        => true,
            'storage_path'     => $storagePath,
            'original_filename'=> 'kop.pdf',
            'mime_type'        => 'application/pdf',
            'file_size_bytes'  => strlen($letterheadBytes),
            'uploaded_by'      => User::factory()->create(['role' => User::ROLE_SUPER_ADMIN])->id,
        ]);
    }

    public function test_receipt_requires_auth(): void
    {
        $response = $this->get('/internal/transactions/1/receipt');
        $response->assertRedirect(route('home', ['login' => 'true']));
    }

    public function test_receipt_forbidden_for_unallowed_role(): void
    {
        $user    = User::factory()->create(['role' => 'viewer']);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Dummy']);

        $trx = ZakatTransaction::query()->create([
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

        $this->actingAs($user)
            ->get('/internal/transactions/' . $trx->id . '/receipt')
            ->assertForbidden();
    }

    public function test_staff_can_print_receipt_pdf_when_letterhead_active(): void
    {
        $this->seedActiveLetterheadTemplate();

        $staff   = User::factory()->create(['role' => User::ROLE_STAFF, 'name' => 'Petugas 1']);
        $muzakki = Muzakki::query()->create([
            'name'   => 'Ahmad',
            'address'=> 'Jl. Contoh',
            'phone'  => '081234',
        ]);

        $trx = ZakatTransaction::query()->create([
            'no_transaksi'                  => 'TRX-20260308-0001',
            'muzakki_id'                    => $muzakki->id,
            'pembayar_nama'                 => 'Hamba Allah',
            'pembayar_phone'                => '0812',
            'pembayar_alamat'               => 'Jakarta',
            'shift'                         => ZakatTransaction::SHIFTS[0],
            'category'                      => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'                   => 2026,
            'metode'                        => ZakatTransaction::METHOD_UANG,
            'nominal_uang'                  => 100000,
            'jumlah_beras_kg'               => null,
            'jiwa'                          => 2,
            'hari'                          => null,
            'is_khusus'                     => false,
            'default_fitrah_cash_per_jiwa_used' => 50000,
            'default_fidyah_per_hari_used'  => null,
            'petugas_id'                    => $staff->id,
            'keterangan'                    => 'Test',
            'status'                        => ZakatTransaction::STATUS_VALID,
            'waktu_terima'                  => now('Asia/Jakarta'),
        ]);

        $response = $this->actingAs($staff)
            ->get('/internal/transactions/' . $trx->id . '/receipt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertIsString($response->getContent());
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_printing_trashed_transaction_is_forbidden_for_staff_but_allowed_for_admin(): void
    {
        $this->seedActiveLetterheadTemplate();

        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin   = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        $trx = ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0002',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 1000,
            'petugas_id'     => $staff->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        $trx->deleted_by     = $admin->id;
        $trx->deleted_reason = 'Salah input';
        $trx->save();
        $trx->delete();

        $this->actingAs($staff)
            ->get('/internal/transactions/' . $trx->id . '/receipt')
            ->assertForbidden();

        $response = $this->actingAs($admin)
            ->get('/internal/transactions/' . $trx->id . '/receipt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_receipt_redirects_with_error_when_no_active_letterhead(): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

        $trx = ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260308-0001',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Hamba Allah',
            'pembayar_phone' => '0812',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 100000,
            'petugas_id'     => $staff->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        $response = $this->actingAs($staff)
            ->get('/internal/transactions/' . $trx->id . '/receipt');

        $response->assertRedirect(route('internal.transactions.create'));
        $response->assertSessionHasErrors(['letterhead']);
    }
}

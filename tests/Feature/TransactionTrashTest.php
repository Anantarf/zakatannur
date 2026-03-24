<?php

namespace Tests\Feature;

use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_requires_auth(): void
    {
        $this->get('/internal/history')->assertRedirect(route('home', ['login' => 'true']));
    }

    public function test_staff_can_view_history(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)->get('/internal/history')
            ->assertOk()
            ->assertSee('Riwayat Transaksi');
    }

    public function test_staff_cannot_trash_transaction(): void
    {
        $staff   = User::factory()->create(['role' => User::ROLE_STAFF]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

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

        $this->actingAs($staff)
            ->post('/internal/transactions/' . $trx->id . '/trash', ['deleted_reason' => 'Salah input'])
            ->assertForbidden();

        $this->assertDatabaseHas('zakat_transactions', [
            'id'        => $trx->id,
            'deleted_at'=> null,
        ]);
    }

    public function test_admin_can_trash_with_reason_and_restore(): void
    {
        $admin   = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $petugas = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Ahmad']);

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

        $this->actingAs($admin)
            ->from('/internal/history')
            ->post('/internal/transactions/' . $trx->id . '/trash', ['deleted_reason' => 'Duplikat'])
            ->assertRedirect(route('internal.transactions.index'));

        $trx->refresh();
        $this->assertTrue($trx->trashed());
        $this->assertSame($admin->id,  $trx->deleted_by);
        $this->assertSame('Duplikat', $trx->deleted_reason);

        $this->actingAs($admin)
            ->from(route('internal.transactions.trash'))
            ->post('/internal/transactions/' . $trx->id . '/restore')
            ->assertRedirect(route('internal.transactions.trash'));

        $trx->refresh();
        $this->assertFalse($trx->trashed());
        $this->assertSame($admin->id, $trx->restored_by);
        $this->assertNotNull($trx->restored_at);
    }

    public function test_trash_page_requires_admin_or_super_admin(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($staff)->get('/internal/transactions/trash')->assertForbidden();
        $this->actingAs($admin)->get('/internal/transactions/trash')->assertOk();
    }
}

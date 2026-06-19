<?php

namespace Tests\Feature;

use App\Models\Muzakki;
use App\Models\User;
use App\Models\ZakatPeriod;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuzakkiCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_muzakki_pages_require_auth(): void
    {
        $this->get('/internal/muzakki')->assertRedirect(route('home', ['login' => 'true']));
    }

    public function test_unallowed_role_cannot_access_muzakki(): void
    {
        $user = User::factory()->create(['role' => 'viewer']);

        $this->actingAs($user)->get('/internal/muzakki')->assertForbidden();
    }

    public function test_staff_can_update_search_and_soft_delete_muzakki(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $m = Muzakki::query()->create([
            'name' => 'Ahmad',
            'phone' => '081234',
            'address' => 'Jl. Contoh',
        ]);

        $this->assertDatabaseCount('muzakki', 1);

        // search
        $this->actingAs($staff)
            ->get('/internal/muzakki?q=081234')
            ->assertOk()
            ->assertSee('Ahmad');

        // update
        $this->actingAs($staff)
            ->patch('/internal/muzakki/' . $m->id, [
                'name' => 'Ahmad Updated',
                'phone' => '081234',
                'address' => 'Jl. Baru',
            ])
            ->assertRedirect(route('internal.muzakki.index'));

        $m->refresh();
        $this->assertSame('Ahmad Updated', $m->name);

        // soft delete
        $this->actingAs($staff)
            ->delete('/internal/muzakki/' . $m->id)
            ->assertRedirect(route('internal.muzakki.index'));

        $this->assertSoftDeleted('muzakki', ['id' => $m->id]);
    }

    public function test_staff_can_view_muzakki_crm_profile(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create([
            'name' => 'Ahmad CRM',
            'phone' => '081234',
            'address' => 'Jl. CRM',
        ]);

        $period = ZakatPeriod::query()->create([
            'code' => 'ramadan-2030-1',
            'label' => 'Ramadan 1451 H',
            'gregorian_year' => 2030,
            'hijri_year' => 1451,
            'hijri_month' => 9,
            'sequence' => 1,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 60000,
            'default_fidyah_beras_per_hari' => 0.75,
        ]);

        ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20300105-0001',
            'muzakki_id' => $muzakki->id,
            'zakat_period_id' => $period->id,
            'hijri_year' => 1451,
            'hijri_month' => 9,
            'pembayar_nama' => 'Ahmad CRM',
            'pembayar_phone' => '081234',
            'pembayar_alamat' => 'Jl. CRM',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2030,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 110000,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'waktu_terima' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($staff)
            ->get('/internal/muzakki/' . $muzakki->id)
            ->assertOk()
            ->assertSee('Profil Muzakki')
            ->assertSee('Ahmad CRM')
            ->assertSee('Rp 110.000')
            ->assertSee('Ramadan 1451 H');
    }

    public function test_admin_can_merge_duplicate_muzakki_safely(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = Muzakki::query()->create([
            'name' => 'Ahmad',
            'phone' => '081234',
            'address' => 'Alamat Utama',
        ]);
        $duplicate = Muzakki::query()->create([
            'name' => 'Ahmad',
            'phone' => '081234',
            'address' => 'Alamat Duplikat',
        ]);

        $tx = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260308-0001',
            'muzakki_id' => $duplicate->id,
            'pembayar_nama' => 'Ahmad',
            'pembayar_phone' => '081234',
            'pembayar_alamat' => 'Alamat Duplikat',
            'shift' => ZakatTransaction::SHIFT_PAGI,
            'category' => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 100000,
            'petugas_id' => $admin->id,
            'status' => ZakatTransaction::STATUS_VALID,
        ]);

        $this->actingAs($admin)
            ->post('/internal/muzakki/' . $target->id . '/merge', [
                'duplicate_id' => $duplicate->id,
                'confirm_name' => 'Ahmad',
            ])
            ->assertRedirect(route('internal.muzakki.show', ['muzakki' => $target->id]));

        $tx->refresh();
        $this->assertSame((int) $target->id, (int) $tx->muzakki_id);
        $this->assertSoftDeleted('muzakki', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('muzakki', [
            'id' => $target->id,
            'deleted_at' => null,
        ]);
    }

    public function test_staff_cannot_merge_duplicate_muzakki(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $target = Muzakki::query()->create(['name' => 'Ahmad']);
        $duplicate = Muzakki::query()->create(['name' => 'Ahmad']);

        $this->actingAs($staff)
            ->post('/internal/muzakki/' . $target->id . '/merge', [
                'duplicate_id' => $duplicate->id,
                'confirm_name' => 'Ahmad',
            ])
            ->assertForbidden();
    }
}



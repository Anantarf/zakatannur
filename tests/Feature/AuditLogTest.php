<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Muzakki;
use App\Models\Template;
use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_trashing_transaction_writes_audit_log(): void
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
            ->post('/internal/transactions/' . $trx->id . '/trash', ['deleted_reason' => 'Duplikat'])
            ->assertRedirect(route('internal.transactions.index'));

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'transaction.receipt_trashed',
            'actor_user_id'=> $admin->id,
        ]);

        $log = \App\Models\AuditLog::where('action', 'transaction.receipt_trashed')->first();
        $this->assertEquals('TRX-20260308-0001', $log->metadata['no_transaksi']);
    }

    public function test_start_new_period_writes_audit_log(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year'         => 2027,
                'backup_confirmed' => 1,
            ])
            ->assertRedirect(route('internal.settings.period.edit'));

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'period.start_new',
            'actor_user_id'=> $admin->id,
        ]);
    }

    public function test_template_upload_and_activation_write_audit_logs(): void
    {
        Storage::fake('local');

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $file  = UploadedFile::fake()->create('kop.pdf', 200, 'application/pdf');

        $this->actingAs($super)->post('/internal/templates/letterhead', [
            'file' => $file,
        ])->assertRedirect(route('internal.templates.letterhead'));

        $tpl = Template::query()->first();
        $this->assertNotNull($tpl);

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'template.uploaded',
            'actor_user_id'=> $super->id,
            'subject_type' => Template::class,
            'subject_id'   => $tpl->id,
        ]);

        $this->actingAs($super)
            ->post('/internal/templates/' . $tpl->id . '/activate')
            ->assertRedirect(route('internal.templates.letterhead'));

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'template.activated',
            'actor_user_id'=> $super->id,
            'subject_type' => Template::class,
            'subject_id'   => $tpl->id,
        ]);
    }

    public function test_user_create_writes_audit_log(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $response = $this->actingAs($superAdmin)
            ->post('/internal/users', [
                'name'     => 'Admin Baru',
                'username' => 'adminnew',
                'role'     => User::ROLE_ADMIN,
                'password' => 'password123',
            ]);

        $created = User::query()->where('username', 'adminnew')->first();
        $this->assertNotNull($created);

        $response->assertRedirect(route('internal.users.edit', ['user' => $created->id]));

        $this->assertDatabaseHas('audit_logs', [
            'action'       => 'user.created',
            'actor_user_id'=> $superAdmin->id,
            'subject_type' => User::class,
            'subject_id'   => $created->id,
        ]);
    }
}

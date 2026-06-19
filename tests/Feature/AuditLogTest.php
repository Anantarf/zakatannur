<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\TransactionRiskReview;
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
            'action'       => 'transaction.delete',
            'actor_user_id'=> $admin->id,
        ]);

        $log = \App\Models\AuditLog::where('action', 'transaction.delete')->first();
        $this->assertEquals('TRX-20260308-0001', $log->metadata['no_transaksi']);
    }

    public function test_start_new_period_writes_audit_log(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year'         => 2027,
                'backup_confirmed' => 1,
                'new_year_confirmation' => '2027',
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
        Storage::disk('local')->makeDirectory('templates/letterhead');

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

    public function test_updating_risk_review_status_writes_audit_log_when_status_changes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $muzakki = Muzakki::query()->create(['name' => 'Muzakki Review']);

        $tx = ZakatTransaction::query()->create([
            'no_transaksi'   => 'TRX-20260517-0099',
            'muzakki_id'     => $muzakki->id,
            'pembayar_nama'  => 'Pembayar Review',
            'pembayar_phone' => '08123',
            'pembayar_alamat'=> 'Jakarta',
            'shift'          => ZakatTransaction::SHIFTS[0],
            'category'       => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat'    => 2026,
            'metode'         => ZakatTransaction::METHOD_UANG,
            'nominal_uang'   => 50000,
            'jiwa'           => 1,
            'petugas_id'     => $admin->id,
            'status'         => ZakatTransaction::STATUS_VALID,
        ]);

        TransactionRiskReview::query()->create([
            'zakat_transaction_id' => $tx->id,
            'group_no_transaksi' => $tx->no_transaksi,
            'risk_level' => TransactionRiskReview::LEVEL_WARNING,
            'risk_score' => 25,
            'risk_flags' => ['updated_after_receipt_printed'],
            'reasons' => ['Transaksi diubah setelah kwitansi pernah dicetak dan perlu verifikasi ulang.'],
            'duplicate_candidates' => [],
            'detector_version' => 'v1',
            'review_status' => TransactionRiskReview::REVIEW_BELUM_DITINJAU,
            'checked_at' => now(config('zakat.timezone')),
        ]);

        $this->actingAs($admin)
            ->patch('/internal/anomalies/' . $tx->no_transaksi . '/review-status', [
                'review_status' => TransactionRiskReview::REVIEW_AMAN,
                'review_note' => 'Operator sudah cek bukti transfer dan data sesuai.',
            ])
            ->assertRedirect('/internal/anomalies/' . $tx->no_transaksi);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.risk_review_status_updated',
            'actor_user_id' => $admin->id,
            'subject_type' => ZakatTransaction::class,
            'subject_id' => $tx->id,
        ]);

        $log = \App\Models\AuditLog::where('action', 'transaction.risk_review_status_updated')->first();
        $this->assertNotNull($log);
        $this->assertEquals($tx->no_transaksi, $log->metadata['no_transaksi']);
        $this->assertEquals(TransactionRiskReview::REVIEW_BELUM_DITINJAU, $log->metadata['previous_review_status']);
        $this->assertEquals(TransactionRiskReview::REVIEW_AMAN, $log->metadata['new_review_status']);
        $this->assertNull($log->metadata['previous_review_note']);
        $this->assertEquals('Operator sudah cek bukti transfer dan data sesuai.', $log->metadata['new_review_note']);
    }

    public function test_first_receipt_print_writes_audit_log(): void
    {
        Storage::fake('local');
        Storage::disk('local')->makeDirectory('templates/letterhead');

        $tcpdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $tcpdf->setPrintHeader(false);
        $tcpdf->setPrintFooter(false);
        $tcpdf->AddPage();
        $letterheadBytes = $tcpdf->Output('', 'S');

        $storagePath = 'templates/letterhead/test_letterhead.pdf';
        Storage::disk('local')->put($storagePath, $letterheadBytes);

        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        Template::query()->create([
            'template_type' => Template::TYPE_LETTERHEAD,
            'version' => 1,
            'is_active' => true,
            'storage_path' => $storagePath,
            'original_filename' => 'kop.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($letterheadBytes),
            'uploaded_by' => $superAdmin->id,
        ]);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Muzakki Print']);

        $tx = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260518-0099',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Pembayar Print',
            'pembayar_phone' => '08123',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFTS[0],
            'category' => ZakatTransaction::CATEGORY_FITRAH,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 50000,
            'jiwa' => 1,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
        ]);

        $this->actingAs($staff)
            ->get('/internal/transactions/' . $tx->id . '/receipt')
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.receipt_printed',
            'actor_user_id' => $staff->id,
            'subject_type' => ZakatTransaction::class,
            'subject_id' => $tx->id,
        ]);
    }

    public function test_admin_update_after_receipt_print_writes_audit_log(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);
        $muzakki = Muzakki::query()->create(['name' => 'Muzakki Update']);

        $tx = ZakatTransaction::query()->create([
            'no_transaksi' => 'TRX-20260518-0100',
            'muzakki_id' => $muzakki->id,
            'pembayar_nama' => 'Pembayar Update',
            'pembayar_phone' => '08123',
            'pembayar_alamat' => 'Jakarta',
            'shift' => ZakatTransaction::SHIFTS[0],
            'category' => ZakatTransaction::CATEGORY_MAL,
            'tahun_zakat' => 2026,
            'metode' => ZakatTransaction::METHOD_UANG,
            'nominal_uang' => 1000,
            'petugas_id' => $staff->id,
            'status' => ZakatTransaction::STATUS_VALID,
            'receipt_printed_at' => now(config('zakat.timezone')),
            'receipt_printed_by' => $staff->id,
        ]);

        $this->actingAs($admin)
            ->patch('/internal/transactions/' . $tx->id , [
                'pembayar_nama' => 'Pembayar Update',
                'pembayar_phone' => '08123',
                'pembayar_alamat' => 'Jakarta',
                'tahun_zakat' => 2026,
                'shift' => ZakatTransaction::SHIFT_PAGI,
                'items' => [
                    [
                        'id' => $tx->id,
                        'muzakki_name' => 'Muzakki Update',
                        'category' => ZakatTransaction::CATEGORY_MAL,
                        'metode' => ZakatTransaction::METHOD_UANG,
                        'nominal_uang' => 2000,
                    ],
                ],
            ])
            ->assertRedirect('/internal/transactions/' . $tx->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'transaction.updated_after_receipt_printed',
            'actor_user_id' => $admin->id,
            'subject_type' => ZakatTransaction::class,
            'subject_id' => $tx->id,
        ]);
    }

    public function test_action_label_and_color_class_are_resolved_consistently(): void
    {
        $log = new \App\Models\AuditLog();
        $log->action = 'login';
        $this->assertSame('Login', $log->action_label);
        $this->assertStringContainsString('blue', $log->action_color_class);

        $log->action = 'transaction.delete';
        $this->assertSame('Dipindah ke Sampah', $log->action_label);
        $this->assertStringContainsString('pink', $log->action_color_class);

        $log->action = 'Restored.Transaction';
        $this->assertSame('Transaksi Dipulihkan', $log->action_label);
        $this->assertStringContainsString('indigo', $log->action_color_class);

        $log->action = 'unknown.action';
        $this->assertSame('Unknown Action', $log->action_label);
        $this->assertStringContainsString('slate', $log->action_color_class);
    }

}

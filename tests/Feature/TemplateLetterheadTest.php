<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateLetterheadTest extends TestCase
{
    use RefreshDatabase;

    public function test_letterhead_templates_requires_auth(): void
    {
        $response = $this->get('/internal/templates/letterhead');

        $response->assertRedirect(route('home', ['login' => 'true']));
    }

    public function test_letterhead_templates_forbidden_for_admin_and_staff(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($admin)->get('/internal/templates/letterhead')->assertForbidden();
        $this->actingAs($staff)->get('/internal/templates/letterhead')->assertForbidden();
    }

    public function test_super_admin_can_upload_pdf_and_see_it_listed(): void
    {
        Storage::fake('local');

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $file = UploadedFile::fake()->create('kop.pdf', 200, 'application/pdf');

        $response = $this->actingAs($super)->post('/internal/templates/letterhead', [
            'file' => $file,
        ]);

        $response->assertRedirect(route('internal.templates.letterhead'));

        $this->assertDatabaseCount('templates', 1);

        $tpl = Template::query()->first();
        $this->assertNotNull($tpl);
        $this->assertSame('letterhead', $tpl->template_type);
        $this->assertSame(1, (int) $tpl->version);
        $this->assertTrue((bool) $tpl->is_active);
        $this->assertSame('kop.pdf', $tpl->original_filename);

        Storage::disk('local')->assertExists($tpl->storage_path);

        $this->actingAs($super)->get('/internal/templates/letterhead')
            ->assertOk()
            ->assertSee('Template Kop Surat');
    }

    public function test_uploading_new_template_replaces_old_and_is_active(): void
    {
        Storage::fake('local');

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $file1 = UploadedFile::fake()->create('kop1.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->create('kop2.pdf', 100, 'application/pdf');

        $this->actingAs($super)->post('/internal/templates/letterhead', ['file' => $file1])
            ->assertRedirect(route('internal.templates.letterhead'));

        $this->assertDatabaseCount('templates', 1);
        $this->assertSame(1, (int) Template::query()->first()->version);

        $this->actingAs($super)->post('/internal/templates/letterhead', ['file' => $file2])
            ->assertRedirect(route('internal.templates.letterhead'));

        // Old template deleted, only new one remains
        $this->assertDatabaseCount('templates', 1);
        $tpl = Template::query()->first();
        $this->assertSame(2, (int) $tpl->version);
        $this->assertTrue((bool) $tpl->is_active);
    }

    public function test_activate_switches_active_template(): void
    {
        Storage::fake('local');

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        // Seed two templates directly to test activate endpoint
        $tplV1 = Template::query()->create([
            'template_type' => Template::TYPE_LETTERHEAD,
            'version' => 1,
            'is_active' => true,
            'storage_path' => 'templates/letterhead/v1.pdf',
            'original_filename' => 'kop1.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 100,
            'uploaded_by' => $super->id,
        ]);
        $tplV2 = Template::query()->create([
            'template_type' => Template::TYPE_LETTERHEAD,
            'version' => 2,
            'is_active' => false,
            'storage_path' => 'templates/letterhead/v2.pdf',
            'original_filename' => 'kop2.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 100,
            'uploaded_by' => $super->id,
        ]);

        $this->actingAs($super)->post('/internal/templates/' . $tplV2->id . '/activate')
            ->assertRedirect(route('internal.templates.letterhead'));

        $this->assertFalse((bool) $tplV1->fresh()->is_active);
        $this->assertTrue((bool) $tplV2->fresh()->is_active);
        $this->assertSame(1, Template::query()->where('is_active', true)->count());
    }

    public function test_old_storage_file_deleted_only_after_successful_upload(): void
    {
        Storage::fake('local');

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        // Upload first template
        $file1 = UploadedFile::fake()->create('kop1.pdf', 100, 'application/pdf');
        $this->actingAs($super)->post('/internal/templates/letterhead', ['file' => $file1]);

        $oldPath = Template::query()->first()->storage_path;
        Storage::disk('local')->assertExists($oldPath);

        // Upload second template — old file should be deleted after commit
        $file2 = UploadedFile::fake()->create('kop2.pdf', 100, 'application/pdf');
        $this->actingAs($super)->post('/internal/templates/letterhead', ['file' => $file2]);

        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists(Template::query()->first()->storage_path);
    }

    public function test_super_admin_can_preview_pdf(): void
    {
        Storage::fake('local');

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $file = UploadedFile::fake()->create('kop.pdf', 120, 'application/pdf');
        $this->actingAs($super)->post('/internal/templates/letterhead', ['file' => $file]);

        $tpl = Template::query()->first();
        $this->assertNotNull($tpl);

        $this->actingAs($super)->get('/internal/templates/' . $tpl->id . '/preview')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}



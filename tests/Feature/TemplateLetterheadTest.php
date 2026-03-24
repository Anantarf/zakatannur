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
        $this->assertFalse((bool) $tpl->is_active);
        $this->assertSame('kop.pdf', $tpl->original_filename);

        Storage::disk('local')->assertExists($tpl->storage_path);

        $this->actingAs($super)->get('/internal/templates/letterhead')
            ->assertOk()
            ->assertSee('Template Kop Surat');
    }

    public function test_super_admin_can_activate_only_one_template(): void
    {
        Storage::fake('local');

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $file1 = UploadedFile::fake()->create('kop1.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->create('kop2.pdf', 100, 'application/pdf');

        $this->actingAs($super)->post('/internal/templates/letterhead', ['file' => $file1]);
        $this->actingAs($super)->post('/internal/templates/letterhead', ['file' => $file2]);

        $this->assertDatabaseCount('templates', 2);

        $tplV1 = Template::query()->where('version', 1)->first();
        $tplV2 = Template::query()->where('version', 2)->first();
        $this->assertNotNull($tplV1);
        $this->assertNotNull($tplV2);

        $this->actingAs($super)->post('/internal/templates/' . $tplV1->id . '/activate')
            ->assertRedirect(route('internal.templates.letterhead'));

        $tplV1->refresh();
        $tplV2->refresh();
        $this->assertTrue((bool) $tplV1->is_active);
        $this->assertFalse((bool) $tplV2->is_active);

        $this->actingAs($super)->post('/internal/templates/' . $tplV2->id . '/activate')
            ->assertRedirect(route('internal.templates.letterhead'));

        $tplV1->refresh();
        $tplV2->refresh();
        $this->assertFalse((bool) $tplV1->is_active);
        $this->assertTrue((bool) $tplV2->is_active);

        $this->assertSame(1, Template::query()->where('template_type', 'letterhead')->where('is_active', true)->count());
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



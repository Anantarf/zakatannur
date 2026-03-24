<?php

namespace Tests\Feature;

use App\Models\Muzakki;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuzakkiCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_muzakki_pages_require_auth(): void
    {
        $this->get('/internal/muzakki')->assertRedirect(route('home', ['login' => 'true']));
        $this->get('/internal/muzakki/create')->assertRedirect(route('home', ['login' => 'true']));
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
}



<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacGatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_redirects_to_login_when_unauthenticated(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('home', ['login' => 'true']));
    }

    public function test_dashboard_forbidden_when_authenticated_with_unallowed_role(): void
    {
        $user = User::factory()->create([
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertForbidden();
    }

    public function test_dashboard_ok_for_staff(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_dashboard_ok_for_super_admin(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_register_route_is_not_available(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }
}



<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EnterpriseFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()
            ->assertJsonStructure(['status', 'db', 'timestamp'])
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('db', 'ok');
    }

    public function test_health_endpoint_requires_no_auth(): void
    {
        $this->getJson('/health')->assertOk();
    }

    public function test_idle_timeout_logs_out_inactive_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($user)
            ->withSession(['last_activity' => time() - (61 * 60)])
            ->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_idle_timeout_keeps_active_user_logged_in(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($user)
            ->withSession(['last_activity' => time() - (30 * 60)])
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_two_factor_middleware_redirects_when_2fa_enabled_but_not_passed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);
        $user->forceFill([
            'two_factor_secret'       => encrypt('TESTSECRET'),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $user->refresh();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/two-factor-challenge');
    }

    public function test_two_factor_middleware_passes_when_session_flag_set(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);
        $user->forceFill([
            'two_factor_secret'       => encrypt('TESTSECRET'),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $user->refresh();

        $this->actingAs($user)
            ->withSession(['2fa_passed' => true])
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_two_factor_middleware_passes_when_2fa_not_enabled(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_two_factor_profile_page_accessible(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($user)
            ->withSession(['2fa_passed' => true])
            ->get('/internal/profile/two-factor')
            ->assertOk();
    }
}

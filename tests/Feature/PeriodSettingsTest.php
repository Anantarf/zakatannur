<?php

namespace Tests\Feature;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_settings_requires_auth(): void
    {
        $response = $this->get('/internal/settings/period');

        $response->assertRedirect(route('home', ['login' => 'true']));
    }

    public function test_period_settings_forbidden_for_staff(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->actingAs($user)->get('/internal/settings/period');

        $response->assertForbidden();
    }

    public function test_admin_can_view_period_settings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($user)->get('/internal/settings/period');

        $response->assertOk();
        $response->assertSee('Konfigurasi Periode');
    }

    public function test_admin_can_update_period_settings_and_defaults(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $payload = [
            'active_year' => 2026,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 60000,
            'public_refresh_interval_seconds' => 15,
        ];

        $response = $this->actingAs($user)->post('/internal/settings/period', $payload);

        $response->assertRedirect(route('internal.settings.period.edit'));

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_ACTIVE_YEAR,
            'value' => '2026',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS,
            'value' => '15',
        ]);

        $this->assertDatabaseHas('annual_settings', [
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.50,
            'default_fidyah_per_hari' => 60000,
        ]);

        $annual = AnnualSetting::query()->where('year', 2026)->first();
        $this->assertNotNull($annual);
    }

    public function test_refresh_interval_validation_allows_zero_or_10_to_60(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $badPayload = [
            'active_year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 50000,
            'public_refresh_interval_seconds' => 5,
        ];

        $response = $this->actingAs($user)->post('/internal/settings/period', $badPayload);

        $response->assertSessionHasErrors(['public_refresh_interval_seconds']);

        $okPayloadOff = $badPayload;
        $okPayloadOff['public_refresh_interval_seconds'] = 0;
        $this->actingAs($user)->post('/internal/settings/period', $okPayloadOff)->assertRedirect(route('internal.settings.period.edit'));

        $okPayloadMin = $badPayload;
        $okPayloadMin['public_refresh_interval_seconds'] = 10;
        $this->actingAs($user)->post('/internal/settings/period', $okPayloadMin)->assertRedirect(route('internal.settings.period.edit'));

        $okPayloadMax = $badPayload;
        $okPayloadMax['public_refresh_interval_seconds'] = 60;
        $this->actingAs($user)->post('/internal/settings/period', $okPayloadMax)->assertRedirect(route('internal.settings.period.edit'));
    }

    public function test_start_new_period_forbidden_for_staff(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2027,
                'backup_confirmed' => 1,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_start_new_period_and_annual_defaults_are_copied(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 60000,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2027,
                'backup_confirmed' => 1,
            ])
            ->assertRedirect(route('internal.settings.period.edit'));

        $this->assertDatabaseHas('app_settings', [
            'key' => AppSetting::KEY_ACTIVE_YEAR,
            'value' => '2027',
        ]);

        $this->assertDatabaseHas('annual_settings', [
            'year' => 2027,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.50,
            'default_fidyah_per_hari' => 60000,
        ]);
    }

    public function test_start_new_period_requires_backup_confirmation_and_new_year_must_be_greater(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // missing backup_confirmed
        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2027,
            ])
            ->assertSessionHasErrors(['backup_confirmed']);

        // new_year not greater
        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2026,
                'backup_confirmed' => 1,
            ])
            ->assertSessionHasErrors(['new_year']);
    }
}



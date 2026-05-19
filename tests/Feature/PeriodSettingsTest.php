<?php

namespace Tests\Feature;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\ZakatPeriod;
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

    public function test_super_admin_can_view_period_settings(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $response = $this->actingAs($user)->get('/internal/settings/period');

        $response->assertOk();
        $response->assertSee('Konfigurasi Periode');
    }

    public function test_super_admin_can_update_period_settings_and_defaults(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $payload = [
            'active_year' => 2026,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 60000,
            'default_fidyah_beras_per_hari' => 0.75,
            'chart_starts_at' => '2026-03-10',
            'chart_ends_at' => '2026-03-25',
            'chart_fallback_buffer_days' => 3,
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
            'default_fidyah_beras_per_hari' => 0.75,
            'chart_starts_at' => '2026-03-10 00:00:00',
            'chart_ends_at' => '2026-03-25 00:00:00',
            'chart_fallback_buffer_days' => 3,
        ]);

        $annual = AnnualSetting::query()->where('year', 2026)->first();
        $this->assertNotNull($annual);

        $this->assertDatabaseHas('zakat_periods', [
            'gregorian_year' => 2026,
            'label' => 'Ramadan 2026',
            'default_fitrah_cash_per_jiwa' => 55000,
            'chart_starts_at' => '2026-03-10 00:00:00',
            'chart_ends_at' => '2026-03-25 00:00:00',
            'is_active' => 1,
        ]);
    }

    public function test_chart_window_end_date_cannot_be_before_start_date(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $response = $this->actingAs($user)->post('/internal/settings/period', [
            'active_year' => 2026,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 60000,
            'default_fidyah_beras_per_hari' => 0.75,
            'chart_starts_at' => '2026-03-25',
            'chart_ends_at' => '2026-03-10',
            'chart_fallback_buffer_days' => 2,
            'public_refresh_interval_seconds' => 15,
        ]);

        $response->assertSessionHasErrors(['chart_ends_at']);
    }

    public function test_refresh_interval_validation_allows_zero_or_10_to_60(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPER_ADMIN,
        ]);

        $badPayload = [
            'active_year' => 2026,
            'default_fitrah_cash_per_jiwa' => 50000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 50000,
            'default_fidyah_beras_per_hari' => 0.75,
            'chart_fallback_buffer_days' => 2,
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
                'new_year_confirmation' => '2027',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_start_new_period_and_annual_defaults_are_copied(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);
        AnnualSetting::query()->create([
            'year' => 2026,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 60000,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2027,
                'backup_confirmed' => 1,
                'new_year_confirmation' => '2027',
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

    public function test_start_new_period_requires_backup_confirmation_and_new_year_cannot_go_backwards(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2026']);

        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        // missing backup_confirmed
        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2027,
                'new_year_confirmation' => '2027',
            ])
            ->assertSessionHasErrors(['backup_confirmed']);

        // new_year cannot go backwards
        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2025,
                'backup_confirmed' => 1,
                'new_year_confirmation' => '2025',
            ])
            ->assertSessionHasErrors(['new_year']);

        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2027,
                'backup_confirmed' => 1,
                'new_year_confirmation' => '2028',
            ])
            ->assertSessionHasErrors(['new_year_confirmation']);
    }

    public function test_same_gregorian_year_can_have_two_ramadan_periods(): void
    {
        AppSetting::query()->create(['key' => AppSetting::KEY_ACTIVE_YEAR, 'value' => '2030']);

        $first = ZakatPeriod::query()->create([
            'code' => 'ramadan-2030-1',
            'label' => 'Ramadan 1451 H',
            'gregorian_year' => 2030,
            'hijri_year' => 1451,
            'hijri_month' => 9,
            'sequence' => 1,
            'is_active' => true,
            'default_fitrah_cash_per_jiwa' => 55000,
            'default_fitrah_beras_per_jiwa' => 2.5,
            'default_fidyah_per_hari' => 60000,
            'default_fidyah_beras_per_hari' => 0.75,
        ]);

        AppSetting::query()->create([
            'key' => AppSetting::KEY_ACTIVE_ZAKAT_PERIOD_ID,
            'value' => (string) $first->id,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->actingAs($admin)
            ->post('/internal/settings/period/start-new', [
                'new_year' => 2030,
                'backup_confirmed' => 1,
                'new_year_confirmation' => '2030',
            ])
            ->assertRedirect(route('internal.settings.period.edit'));

        $this->assertDatabaseHas('zakat_periods', [
            'gregorian_year' => 2030,
            'sequence' => 2,
            'is_active' => 1,
        ]);

        $this->assertSame(2, ZakatPeriod::query()->where('gregorian_year', 2030)->count());
        $this->assertSame(1, ZakatPeriod::query()->where('gregorian_year', 2030)->where('is_active', true)->count());
    }
}



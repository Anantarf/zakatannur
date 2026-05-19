<?php

namespace Database\Seeders;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\User;
use App\Models\ZakatPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $activeYear = 2026;

        $annual = AnnualSetting::query()->firstOrCreate(
            ['year' => $activeYear],
            [
                'default_fitrah_cash_per_jiwa' => 50000,
                'default_fidyah_per_hari' => 50000,
            ]
        );

        AppSetting::query()->updateOrCreate(['key' => AppSetting::KEY_ACTIVE_YEAR], ['value' => (string) $activeYear]);
        AppSetting::query()->updateOrCreate(['key' => AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS], ['value' => '15']);

        $period = ZakatPeriod::query()->firstOrCreate(
            ['gregorian_year' => $activeYear, 'sequence' => 1],
            [
                'code' => 'ramadan-' . $activeYear . '-1',
                'label' => 'Ramadan ' . $activeYear,
                'hijri_month' => 9,
                'default_fitrah_cash_per_jiwa' => (int) $annual->default_fitrah_cash_per_jiwa,
                'default_fitrah_beras_per_jiwa' => (float) ($annual->default_fitrah_beras_per_jiwa ?? 2.50),
                'default_fidyah_per_hari' => (int) $annual->default_fidyah_per_hari,
                'default_fidyah_beras_per_hari' => (float) ($annual->default_fidyah_beras_per_hari ?? 0.75),
                'chart_fallback_buffer_days' => (int) ($annual->chart_fallback_buffer_days ?? 2),
                'is_active' => true,
            ]
        );

        AppSetting::query()->updateOrCreate(['key' => AppSetting::KEY_ACTIVE_ZAKAT_PERIOD_ID], ['value' => (string) $period->id]);

        User::query()->firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'role' => User::ROLE_SUPER_ADMIN,
                'password' => Hash::make('password'),
            ]
        );
    }
}

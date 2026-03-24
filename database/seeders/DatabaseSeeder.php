<?php

namespace Database\Seeders;

use App\Models\AnnualSetting;
use App\Models\AppSetting;
use App\Models\User;
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

        AnnualSetting::query()->firstOrCreate(
            ['year' => $activeYear],
            [
                'default_fitrah_cash_per_jiwa' => 50000,
                'default_fidyah_per_hari' => 50000,
            ]
        );

        AppSetting::query()->updateOrCreate(['key' => AppSetting::KEY_ACTIVE_YEAR], ['value' => (string) $activeYear]);
        AppSetting::query()->updateOrCreate(['key' => AppSetting::KEY_PUBLIC_REFRESH_INTERVAL_SECONDS], ['value' => '15']);

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

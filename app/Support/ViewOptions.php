<?php

namespace App\Support;

use App\Models\AnnualSetting;
use App\Models\User;
use App\Models\ZakatPeriod;

final class ViewOptions
{
    /** @return array<int,int> */
    public static function years(int $activeYear): array
    {
        $years = AnnualSetting::query()->orderByDesc('year')->pluck('year')->all();
        $years[] = $activeYear;

        $years = array_values(array_unique(array_map('intval', $years)));
        rsort($years);

        return !empty($years) ? $years : [$activeYear];
    }

    /**
     * @return \Illuminate\Support\Collection<int,\App\Models\ZakatPeriod>
     */
    public static function periods()
    {
        return ZakatPeriod::query()
            ->orderByDesc('gregorian_year')
            ->orderByDesc('sequence')
            ->get(['id', 'label', 'gregorian_year', 'hijri_year', 'sequence', 'is_active']);
    }

    /**
     * @return \Illuminate\Support\Collection<int,\App\Models\User>
     */
    public static function petugasOptions()
    {
        return User::query()
            ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}

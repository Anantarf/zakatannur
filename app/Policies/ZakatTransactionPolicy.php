<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ZakatTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ZakatTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * Intercept all checks - admin/super_admin bypass all restrictions.
     */
    public function before(User $user, $ability)
    {
        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true)) {
            return true;
        }
    }

    /**
     * Staff can only update their own transactions within the same day.
     */
    public function update(User $user, ZakatTransaction $zakatTransaction)
    {
        // 1. Can only edit their own transactions
        if ((int)$zakatTransaction->petugas_id !== (int)$user->id) {
            return Response::deny('Anda hanya dapat mengedit transaksi yang Anda layani sendiri.');
        }

        // 2. Can only edit today's transactions (within same calendar date)
        $txDate = ($zakatTransaction->waktu_terima ?? $zakatTransaction->created_at)->timezone(config('zakat.timezone'));
        if (!$txDate->isToday()) {
            return Response::deny('Batas waktu pengeditan harian telah berakhir. Silakan hubungi Admin untuk perubahan data hari sebelumnya.');
        }

        return Response::allow();
    }
}

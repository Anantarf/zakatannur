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
     * Intercept all checks.
     */
    public function before(User $user, $ability)
    {
        if (in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true)) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ZakatTransaction $zakatTransaction)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ZakatTransaction $zakatTransaction)
    {
        // 1. Can only edit their own transactions
        if ((int)$zakatTransaction->petugas_id !== (int)$user->id) {
            return Response::deny('Anda hanya dapat mengedit transaksi yang Anda layani sendiri.');
        }

        // 2. Can only edit today's transactions (within same calendar date)
        $txDate = ($zakatTransaction->waktu_terima ?? $zakatTransaction->created_at)->timezone('Asia/Jakarta');
        if (!$txDate->isToday()) {
            return Response::deny('Batas waktu pengeditan harian telah berakhir. Silakan hubungi Admin untuk perubahan data hari sebelumnya.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ZakatTransaction $zakatTransaction)
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ZakatTransaction $zakatTransaction)
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ZakatTransaction $zakatTransaction)
    {
        return false;
    }
}

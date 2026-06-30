<?php

namespace App\Services\Transactions;

use Illuminate\Support\Facades\DB;

class TransactionLockManager
{
    /**
     * Acquires a named database-level lock.
     * Cache locks are unsafe here with the file driver because the sequence is
     * persisted in the database. The lock must live beside the row reads/writes.
     *
     * @return array{driver:string,name:string}|null
     */
    public function acquire(string $lockName): ?array
    {
        $driver  = DB::connection()->getDriverName();
        $timeout = (int) config('zakat.cache.lock_timeout_seconds', 30);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $result = DB::selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$lockName, $timeout]);

            if ((int) ($result->acquired ?? 0) !== 1) {
                throw new \RuntimeException("Gagal mendapatkan kunci transaksi setelah menunggu (Lock: {$lockName}). Silakan coba lagi.");
            }

            return ['driver' => $driver, 'name' => $lockName];
        }

        if ($driver === 'pgsql') {
            DB::select('SELECT pg_advisory_xact_lock(hashtext(?))', [$lockName]);
        }

        return null;
    }

    /**
     * @param array{driver:string,name:string}|null $lockToken
     */
    public function release(?array $lockToken): void
    {
        if ($lockToken === null) {
            return;
        }

        if (in_array($lockToken['driver'], ['mysql', 'mariadb'], true)) {
            DB::select('SELECT RELEASE_LOCK(?)', [$lockToken['name']]);
        }
    }
}

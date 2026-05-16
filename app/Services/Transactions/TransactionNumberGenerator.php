<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionNumberGenerator
{
    public function generate(Carbon $time): string
    {
        $prefix = 'TRX-' . $time->format('Ymd') . '-';
        $last = ZakatTransaction::withTrashed()
            ->where('no_transaksi', 'like', $prefix . '%')
            ->orderByRaw(
                DB::getDriverName() === 'sqlite'
                    ? 'CAST(SUBSTR(no_transaksi, 14) AS INTEGER) DESC'
                    : 'CAST(SUBSTRING(no_transaksi, 14) AS UNSIGNED) DESC'
            )
            ->orderByDesc('id')
            ->value('no_transaksi');

        $sequence = ($last && preg_match('/(\d{4})$/', $last, $matches)) ? (int) $matches[1] + 1 : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}

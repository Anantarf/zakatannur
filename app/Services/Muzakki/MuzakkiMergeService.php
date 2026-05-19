<?php

namespace App\Services\Muzakki;

use App\Models\Muzakki;
use App\Models\ZakatTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MuzakkiMergeService
{
    public function mergeInto(Muzakki $target, Muzakki $duplicate): array
    {
        if ($target->is($duplicate)) {
            throw new InvalidArgumentException('Muzakki target dan duplikat tidak boleh sama.');
        }

        return DB::transaction(function () use ($target, $duplicate) {
            $movedTransactions = ZakatTransaction::withTrashed()
                ->where('muzakki_id', $duplicate->id)
                ->update(['muzakki_id' => $target->id]);

            $duplicate->delete();

            return [
                'target_id' => $target->id,
                'duplicate_id' => $duplicate->id,
                'moved_transactions' => $movedTransactions,
            ];
        });
    }
}

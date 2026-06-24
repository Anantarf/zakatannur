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
        return DB::transaction(function () use ($target, $duplicate) {
            $target = Muzakki::lockForUpdate()->find($target->id);
            $duplicate = Muzakki::lockForUpdate()->find($duplicate->id);

            if (!$target || !$duplicate) {
                throw new InvalidArgumentException('Muzakki target atau duplikat tidak ditemukan atau sudah dihapus.');
            }

            if ($target->is($duplicate)) {
                throw new InvalidArgumentException('Muzakki target dan duplikat tidak boleh sama.');
            }

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

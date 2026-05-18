<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionReceiptLifecycleService
{
    public function markGroupAsPrinted(Request $request, ZakatTransaction $transaction): bool
    {
        $noTransaksi = $transaction->no_transaksi;
        $printedAt = now(config('zakat.timezone'));
        $actorId = (int) $request->user()->id;

        $alreadyPrinted = ZakatTransaction::withTrashed()
            ->where('no_transaksi', $noTransaksi)
            ->whereNotNull('receipt_printed_at')
            ->exists();

        DB::transaction(function () use ($noTransaksi, $printedAt, $actorId) {
            ZakatTransaction::withTrashed()
                ->where('no_transaksi', $noTransaksi)
                ->whereNull('receipt_printed_at')
                ->update([
                    'receipt_printed_at' => $printedAt,
                    'receipt_printed_by' => $actorId,
                ]);
        });

        if ($alreadyPrinted) {
            return false;
        }

        Audit::log($request, 'transaction.receipt_printed', $transaction, [
            'no_transaksi' => $noTransaksi,
            'printed_at' => $printedAt->toIso8601String(),
            'printed_by' => $actorId,
            'items_count' => ZakatTransaction::withTrashed()->where('no_transaksi', $noTransaksi)->count(),
        ]);

        return true;
    }
}

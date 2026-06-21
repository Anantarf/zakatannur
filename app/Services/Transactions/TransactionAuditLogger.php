<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use App\Support\Audit;
use Illuminate\Http\Request;

class TransactionAuditLogger
{
    /**
     * @param array{uang:int,beras:float} $oldTotals
     * @param array<int, ZakatTransaction> $results
     */
    public function logSync(
        Request $request,
        string $noTransaksi,
        string $payerName,
        array $summary,
        array $oldTotals,
        array $results,
        bool $isUpdate,
        bool $wasReceiptPrinted
    ): void {
        $newTotals = $this->newTotals($results);
        $subject = count($results) > 0 ? $results[0] : null;

        Audit::log($request, $isUpdate ? 'Updated.Transaction' : 'Created.Transaction', $subject, [
            'no_transaksi' => $noTransaksi,
            'pembayar' => $payerName,
            'summary' => $summary,
            'after_receipt_printed' => $wasReceiptPrinted,
            'totals' => [
                'old' => ['uang' => $oldTotals['uang'], 'beras' => $oldTotals['beras']],
                'new' => $newTotals,
            ],
        ]);

        if ($isUpdate && $wasReceiptPrinted && count($results) > 0) {
            Audit::log($request, 'transaction.updated_after_receipt_printed', $results[0], [
                'no_transaksi' => $noTransaksi,
                'pembayar' => $payerName,
                'summary' => $summary,
                'totals' => [
                    'old' => ['uang' => $oldTotals['uang'], 'beras' => $oldTotals['beras']],
                    'new' => $newTotals,
                ],
            ]);
        }
    }

    /** @param array<int, ZakatTransaction> $results */
    private function newTotals(array $results): array
    {
        return [
            'uang' => (int) collect($results)->sum('nominal_uang'),
            'beras' => (float) collect($results)->sum('jumlah_beras_kg'),
        ];
    }
}

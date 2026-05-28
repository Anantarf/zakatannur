<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;

class TransactionAnomalyDetector
{
    public function analyze(ZakatTransaction $transaction): array
    {
        $score = 0;
        $flags = [];
        $reasons = [];
        $context = (array) ($transaction->getAttribute('anomaly_context') ?? []);

        if (($context['restored_after_delete'] ?? false) === true) {
            $score += 25;
            $flags[] = 'restored_after_delete';
            $reasons[] = 'Transaksi ini sempat dihapus lalu dipulihkan kembali ke riwayat aktif.';
        }

        if (($context['updated_after_receipt_printed'] ?? false) === true) {
            $score += 30;
            $flags[] = 'updated_after_receipt_printed';
            $reasons[] = 'Transaksi diubah setelah kwitansi pernah dicetak dan perlu verifikasi ulang.';
        }

        if (($context['significant_nominal_change'] ?? false) === true) {
            $score += 35;
            $flags[] = 'significant_nominal_change';
            $reasons[] = $this->significantChangeReason($transaction, $context);
        }

        return [
            'score' => $score,
            'flags' => $flags,
            'reasons' => $reasons,
        ];
    }

    private function significantChangeReason(ZakatTransaction $transaction, array $context): string
    {
        $oldUang = (int) ($context['old_total_uang'] ?? 0);
        $newUang = (int) ($context['new_total_uang'] ?? 0);
        $oldBeras = (float) ($context['old_total_beras'] ?? 0);
        $newBeras = (float) ($context['new_total_beras'] ?? 0);

        if ($transaction->metode === ZakatTransaction::METHOD_BERAS || abs($newBeras - $oldBeras) > 0.001) {
            return sprintf(
                'Total beras grup berubah signifikan dari %.2f kg menjadi %.2f kg.',
                $oldBeras,
                $newBeras
            );
        }

        return sprintf(
            'Total nominal grup berubah signifikan dari Rp%d menjadi Rp%d.',
            $oldUang,
            $newUang
        );
    }
}

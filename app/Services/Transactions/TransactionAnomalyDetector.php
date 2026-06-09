<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TransactionAnomalyDetector
{
    private const OUTLIER_MULTIPLIER = 5;
    private const OUTLIER_SCORE = 15;
    private const OUTLIER_MIN_SAMPLE = 10;
    private const AVG_CACHE_TTL_SECONDS = 300;

    public function analyze(ZakatTransaction $transaction): array
    {
        $score = 0;
        $flags = [];
        $reasons = [];
        $context = (array) ($transaction->getAttribute('anomaly_context') ?? []);

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

        $outlierResult = $this->checkStatisticalOutlier($transaction);
        if ($outlierResult !== null) {
            $score += self::OUTLIER_SCORE;
            $flags[] = 'statistical_outlier';
            $reasons[] = $outlierResult;
        }

        return [
            'score' => $score,
            'flags' => $flags,
            'reasons' => $reasons,
        ];
    }

    private function checkStatisticalOutlier(ZakatTransaction $transaction): ?string
    {
        if ($transaction->metode === ZakatTransaction::METHOD_BERAS) {
            return null;
        }

        $nominal = (int) $transaction->nominal_uang;
        if ($nominal <= 0) {
            return null;
        }

        $avg = $this->averageNominalUang();
        if ($avg === null || $avg <= 0) {
            return null;
        }

        $threshold = $avg * self::OUTLIER_MULTIPLIER;

        if ($nominal > $threshold) {
            return sprintf(
                'Nominal Rp%s melebihi %dx rata-rata penerimaan uang (rata-rata: Rp%s).',
                number_format($nominal, 0, ',', '.'),
                self::OUTLIER_MULTIPLIER,
                number_format((int) $avg, 0, ',', '.')
            );
        }

        return null;
    }

    private function averageNominalUang(): ?float
    {
        return Cache::remember('anomaly:avg_nominal_uang', self::AVG_CACHE_TTL_SECONDS, function () {
            $stats = DB::table('zakat_transactions')
                ->whereNull('deleted_at')
                ->where('metode', '!=', ZakatTransaction::METHOD_BERAS)
                ->where('nominal_uang', '>', 0)
                ->selectRaw('AVG(nominal_uang) as avg_nominal, COUNT(*) as total')
                ->first();

            if (!$stats || (int) $stats->total < self::OUTLIER_MIN_SAMPLE) {
                return null;
            }

            return (float) $stats->avg_nominal;
        });
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

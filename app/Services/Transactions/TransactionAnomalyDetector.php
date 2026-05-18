<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use Illuminate\Support\Collection;

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

        if ($this->shouldFlagInfaqOutlier($transaction)) {
            $score += 20;
            $flags[] = 'infaq_outlier';
            $reasons[] = $this->infaqOutlierReason($transaction);
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

    private function shouldFlagInfaqOutlier(ZakatTransaction $transaction): bool
    {
        if ($transaction->category !== ZakatTransaction::CATEGORY_INFAK || $transaction->metode !== ZakatTransaction::METHOD_UANG) {
            return false;
        }

        $baseline = $this->infaqBaseline($transaction);
        if ($baseline === null) {
            return false;
        }

        $nominal = (int) ($transaction->nominal_uang ?? 0);
        if ($nominal <= 0) {
            return false;
        }

        $median = $baseline['median'];
        $mad = $baseline['mad'];

        if ($mad > 0.0) {
            $robustSigma = 1.4826 * $mad;
            if ($robustSigma <= 0.0) {
                return false;
            }

            return abs($nominal - $median) / $robustSigma >= (float) config('zakat.anomaly.infaq_outlier.robust_z_threshold', 6.0);
        }

        if ($median <= 0) {
            return false;
        }

        $upperRatio = (float) config('zakat.anomaly.infaq_outlier.zero_mad_upper_ratio', 5.0);
        $lowerRatio = (float) config('zakat.anomaly.infaq_outlier.zero_mad_lower_ratio', 0.2);
        $ratio = $nominal / $median;

        return $ratio >= $upperRatio || $ratio <= $lowerRatio;
    }

    private function infaqOutlierReason(ZakatTransaction $transaction): string
    {
        $baseline = $this->infaqBaseline($transaction);
        $median = (int) ($baseline['median'] ?? 0);
        $samples = (int) ($baseline['count'] ?? 0);

        return sprintf(
            'Nominal infaq Rp%d cukup jauh dari pola histori infaq (median Rp%d, sampel %d transaksi) dan perlu dicek ulang.',
            (int) ($transaction->nominal_uang ?? 0),
            $median,
            $samples
        );
    }

    private function infaqBaseline(ZakatTransaction $transaction): ?array
    {
        $minSample = (int) config('zakat.anomaly.infaq_outlier.min_sample_size', 10);

        $values = ZakatTransaction::query()
            ->valid()
            ->where('category', ZakatTransaction::CATEGORY_INFAK)
            ->where('metode', ZakatTransaction::METHOD_UANG)
            ->where('id', '!=', $transaction->id)
            ->whereNotNull('nominal_uang')
            ->pluck('nominal_uang')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->values();

        if ($values->count() < $minSample) {
            return null;
        }

        $median = $this->median($values);
        $deviations = $values->map(fn (int $value) => abs($value - $median))->values();
        $mad = $this->median($deviations);

        return [
            'count' => $values->count(),
            'median' => $median,
            'mad' => $mad,
        ];
    }

    private function median(Collection $values): float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return 0.0;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return (float) $sorted[$middle];
        }

        return ((float) $sorted[$middle - 1] + (float) $sorted[$middle]) / 2;
    }
}

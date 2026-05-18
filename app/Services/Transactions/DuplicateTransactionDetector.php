<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DuplicateTransactionDetector
{
    public function analyze(ZakatTransaction $transaction): array
    {
        $score = 0;
        $flags = [];
        $reasons = [];
        $candidates = [];

        $windowStart = $this->transactionTime($transaction)?->copy()->subMinutes(30);
        $windowEnd = $this->transactionTime($transaction)?->copy()->addMinutes(30);

        if (!$windowStart || !$windowEnd) {
            return compact('score', 'flags', 'reasons', 'candidates');
        }

        $candidateRows = ZakatTransaction::query()
            ->with(['muzakki' => fn ($query) => $query->withTrashed()])
            ->where('id', '!=', $transaction->id)
            ->where('no_transaksi', '!=', $transaction->no_transaksi)
            ->where('tahun_zakat', $transaction->tahun_zakat)
            ->where('category', $transaction->category)
            ->where('metode', $transaction->metode)
            ->whereBetween('waktu_terima', [$windowStart, $windowEnd])
            ->get();

        foreach ($candidateRows as $candidate) {
            $candidateScore = 0;
            $matchType = null;

            if ($this->sameTransactionShape($transaction, $candidate) && (int) $candidate->muzakki_id === (int) $transaction->muzakki_id) {
                $candidateScore = 60;
                $matchType = 'exact_duplicate';
            } elseif ($this->samePayerAndAmount($transaction, $candidate) && (int) $candidate->muzakki_id === (int) $transaction->muzakki_id) {
                $candidateScore = 40;
                $matchType = 'payer_match_same_beneficiary';
            } elseif ($this->samePayerAndAmount($transaction, $candidate) && $this->isTransferPair($transaction, $candidate)) {
                $candidateScore = 50;
                $matchType = 'transfer_duplicate_candidate';
            } elseif ($this->samePayerAndAmount($transaction, $candidate)) {
                $candidateScore = 10;
                $matchType = 'payer_match_different_beneficiary';
            }

            if ($candidateScore <= 0 || $matchType === null) {
                continue;
            }

            $score = max($score, $candidateScore);
            $flags[] = $matchType;

            if ($matchType === 'exact_duplicate') {
                $reasons[] = 'Transaksi sangat mirip ditemukan pada tahun zakat yang sama dengan muzakki, nilai, dan waktu yang berdekatan.';
            } elseif ($matchType === 'transfer_duplicate_candidate') {
                $reasons[] = 'Kandidat duplikasi transfer ditemukan dengan nominal dan pembayar yang sama dalam rentang waktu dekat.';
            } elseif ($matchType === 'payer_match_same_beneficiary') {
                $reasons[] = 'Transaksi mirip ditemukan dengan pembayar dan muzakki yang sama dalam rentang waktu dekat.';
            }

            $candidates[] = [
                'transaction_id' => $candidate->id,
                'no_transaksi' => $candidate->no_transaksi,
                'pembayar_nama' => $candidate->pembayar_nama,
                'muzakki_name' => $candidate->muzakki?->name ?? '-',
                'match_type' => $matchType,
                'time_diff_minutes' => abs($this->transactionTime($transaction)?->diffInMinutes($this->transactionTime($candidate)) ?? 0),
            ];
        }

        return [
            'score' => $score,
            'flags' => array_values(array_unique($flags)),
            'reasons' => array_values(array_unique($reasons)),
            'candidates' => $this->deduplicateCandidates(collect($candidates))->values()->all(),
        ];
    }

    private function transactionTime(ZakatTransaction $transaction): ?CarbonInterface
    {
        return $transaction->waktu_terima ?? $transaction->created_at;
    }

    private function sameTransactionShape(ZakatTransaction $left, ZakatTransaction $right): bool
    {
        return $this->sameAmount($left, $right)
            && $this->samePayer($left, $right);
    }

    private function samePayerAndAmount(ZakatTransaction $left, ZakatTransaction $right): bool
    {
        return $this->samePayer($left, $right)
            && $this->sameAmount($left, $right);
    }

    private function samePayer(ZakatTransaction $left, ZakatTransaction $right): bool
    {
        $leftPhone = trim((string) ($left->pembayar_phone ?? ''));
        $rightPhone = trim((string) ($right->pembayar_phone ?? ''));

        if ($leftPhone !== '' && $rightPhone !== '') {
            return $leftPhone === $rightPhone
                && mb_strtolower(trim((string) $left->pembayar_nama)) === mb_strtolower(trim((string) $right->pembayar_nama));
        }

        return mb_strtolower(trim((string) $left->pembayar_nama)) === mb_strtolower(trim((string) $right->pembayar_nama));
    }

    private function sameAmount(ZakatTransaction $left, ZakatTransaction $right): bool
    {
        if ($left->metode === ZakatTransaction::METHOD_BERAS) {
            return abs((float) $left->jumlah_beras_kg - (float) $right->jumlah_beras_kg) < 0.001;
        }

        return (int) $left->nominal_uang === (int) $right->nominal_uang;
    }

    private function isTransferPair(ZakatTransaction $left, ZakatTransaction $right): bool
    {
        return (bool) $left->is_transfer && (bool) $right->is_transfer;
    }

    private function deduplicateCandidates(Collection $candidates): Collection
    {
        return $candidates->unique(fn (array $candidate) => $candidate['transaction_id'] . ':' . $candidate['match_type']);
    }
}

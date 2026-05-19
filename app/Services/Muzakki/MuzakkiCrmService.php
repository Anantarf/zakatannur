<?php

namespace App\Services\Muzakki;

use App\Models\Muzakki;
use App\Models\ZakatTransaction;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MuzakkiCrmService
{
    public function profile(Muzakki $muzakki): array
    {
        $transactions = ZakatTransaction::query()
            ->with(['zakatPeriod', 'petugas'])
            ->where('muzakki_id', $muzakki->id)
            ->valid()
            ->orderByDesc('waktu_terima')
            ->orderByDesc('id')
            ->get();

        $lastTransaction = $transactions->first();
        $firstTransaction = $transactions->last();
        $totalUang = (int) $transactions->sum('nominal_uang');
        $totalBeras = (float) $transactions->sum('jumlah_beras_kg');

        return [
            'summary' => [
                'transaction_count' => $transactions->count(),
                'total_uang' => $totalUang,
                'total_uang_display' => Format::rupiah($totalUang),
                'total_beras' => $totalBeras,
                'total_beras_display' => Format::kg($totalBeras),
                'first_transaction_at' => $this->dateLabel($firstTransaction?->waktu_terima),
                'last_transaction_at' => $this->dateLabel($lastTransaction?->waktu_terima),
                'segment' => $this->segment($lastTransaction?->waktu_terima, $transactions->count()),
            ],
            'periods' => $this->periodSummary($transactions),
            'recent_transactions' => $transactions->take(10),
            'possible_duplicates' => $this->possibleDuplicates($muzakki),
        ];
    }

    private function periodSummary(Collection $transactions): Collection
    {
        return $transactions
            ->groupBy(fn (ZakatTransaction $tx) => (string) ($tx->zakat_period_id ?: 'year-' . $tx->tahun_zakat))
            ->map(function (Collection $items) {
                $first = $items->first();
                $totalUang = (int) $items->sum('nominal_uang');
                $totalBeras = (float) $items->sum('jumlah_beras_kg');

                return [
                    'label' => $first->zakatPeriod?->display_label ?? (string) $first->tahun_zakat,
                    'year' => (int) $first->tahun_zakat,
                    'count' => $items->count(),
                    'total_uang_display' => Format::rupiah($totalUang),
                    'total_beras_display' => Format::kg($totalBeras),
                    'last_at' => $this->dateLabel($items->max('waktu_terima')),
                ];
            })
            ->sortByDesc('year')
            ->values();
    }

    private function possibleDuplicates(Muzakki $muzakki): Collection
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $muzakki->phone);
        $name = trim((string) $muzakki->name);

        return Muzakki::query()
            ->whereKeyNot($muzakki->id)
            ->where(function ($query) use ($phone, $name) {
                if ($phone !== '') {
                    $query->orWhere('phone', $phone);
                }

                if ($name !== '') {
                    $query->orWhere('name', $name);
                }
            })
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'phone', 'address']);
    }

    private function segment($lastTransactionAt, int $count): array
    {
        if ($count === 0 || $lastTransactionAt === null) {
            return [
                'label' => 'Belum Ada Riwayat',
                'tone' => 'muted',
                'description' => 'Belum ada transaksi valid yang tercatat.',
            ];
        }

        $lastAt = Carbon::parse($lastTransactionAt, config('zakat.timezone'));
        $daysSinceLast = $lastAt->diffInDays(now(config('zakat.timezone')));

        if ($daysSinceLast > 365) {
            return [
                'label' => 'Perlu Follow Up',
                'tone' => 'warning',
                'description' => 'Terakhir transaksi lebih dari setahun lalu.',
            ];
        }

        if ($count === 1) {
            return [
                'label' => 'Muzakki Baru',
                'tone' => 'info',
                'description' => 'Baru memiliki satu transaksi valid.',
            ];
        }

        return [
            'label' => 'Aktif',
            'tone' => 'success',
            'description' => 'Memiliki riwayat transaksi terbaru.',
        ];
    }

    private function dateLabel($value): string
    {
        if (!$value) {
            return '-';
        }

        return Carbon::parse($value, config('zakat.timezone'))
            ->timezone(config('zakat.timezone'))
            ->locale('id')
            ->translatedFormat('d M Y');
    }
}

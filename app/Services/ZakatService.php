<?php

namespace App\Services;

use App\Models\AnnualSetting;
use App\Models\Muzakki;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use App\Support\Format;
use App\Support\Audit;
use App\Events\ZakatTransactionCreated;
use Illuminate\Database\Eloquent\Collection;

class ZakatService
{
    private const NO_TRANSAKSI_RETRY_ATTEMPTS = 5;
    private array $annualCache = [];

    public function storeTransaction(array $data, int $petugasId, ?string $noTransaksiOverride = null): array
    {
        $waktuTerima = $this->parseWaktuTerima($data['waktu_terima'] ?? null, $noTransaksiOverride);
        return $this->syncTransactions($noTransaksiOverride, $data, $petugasId, $waktuTerima);
    }

    public function syncTransactions(?string $noTransaksiOverride, array $data, int $petugasId, ?Carbon $waktuTerima = null): array
    {
        $waktuTerima = $waktuTerima ?? $this->parseWaktuTerima($data['waktu_terima'] ?? null);
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [$data];
        
        $oldUang = 0;
        $oldBeras = 0;

        $syncResults = $this->executeWithRetry(function () use ($data, $items, $petugasId, $waktuTerima, $noTransaksiOverride, &$oldUang, &$oldBeras) {
            $lockName = 'sync_tx_' . $waktuTerima->format('Ymd');
            $lock = Cache::lock($lockName, 10);
            
            try {
                if (!$lock->get()) {
                    throw new \RuntimeException("Gagal mendapatkan kunci transaksi setelah menunggu (Lock: {$lockName}). Silakan coba lagi.");
                }
                
                $results = [];
                $noTransaksi = $noTransaksiOverride ?? $this->generateNoTransaksi($waktuTerima);

            // Prevent hijacking existing transaction numbers
            if (!$noTransaksiOverride) {
                if (ZakatTransaction::where('no_transaksi', $noTransaksi)->exists()) {
                    throw new \RuntimeException("Nomor Transaksi {$noTransaksi} sudah terpakai. Sila klik simpan sekali lagi untuk mendapatkan nomor baru.");
                }
            }

            $oldTotals = ZakatTransaction::where('no_transaksi', $noTransaksi)
                ->selectRaw('SUM(nominal_uang) as uang, SUM(jumlah_beras_kg) as beras')
                ->first();
            $oldUang = (int) ($oldTotals->uang ?? 0);
            $oldBeras = (float) ($oldTotals->beras ?? 0);

            $pembayarData = [
                'muzakki_name'    => $data['pembayar_nama'],
                'muzakki_phone'   => $data['pembayar_phone'] ?? '',
                'muzakki_address' => $data['pembayar_alamat'],
            ];

            Muzakki::firstOrCreateNormalized($pembayarData);

            // For EDIT: The system maintains the same No. Transaksi. 
            // We allow name corrections (e.g. typos) but the No. Transaksi remains the primary anchor.

            $existingIds = ZakatTransaction::where('no_transaksi', $noTransaksi)->pluck('id')->toArray();
            $newIds = [];

            foreach ($items as $item) {
                $category = $item['category'] ?? $data['category'] ?? null;
                $metode = $item['metode'] ?? $data['metode'] ?? null;

                if (!$category || !$metode) continue;

                $tahunZakat = (int) ($item['tahun_zakat'] ?? $data['tahun_zakat'] ?? $waktuTerima->year);
                [$defaultFitrah, $defaultFidyah, $defaultBerasKg, $defaultFidyahBeras] = $this->getAnnualDefaults($tahunZakat);

                $itemForComputation = array_merge($item, ['category' => $category, 'metode' => $metode, 'tahun_zakat' => $tahunZakat]);
                $nominalUang = ZakatTransaction::computeNominalUang($itemForComputation, $defaultFitrah, $defaultFidyah);
                $jumlahBerasKg = ZakatTransaction::computeJumlahBerasKg($itemForComputation, $defaultBerasKg, $defaultFidyahBeras);
                $isKhusus = $this->determineIsKhusus($itemForComputation, $defaultFitrah, $defaultFidyah, $defaultBerasKg, $defaultFidyahBeras);

                $itemMuzakkiData = [
                    'muzakki_name'    => $item['muzakki_name'] ?? $pembayarData['muzakki_name'],
                    'muzakki_phone'   => $item['muzakki_phone'] ?? $pembayarData['muzakki_phone'] ?? '',
                    'muzakki_address' => $item['muzakki_address'] ?? $pembayarData['muzakki_address'] ?? '',
                ];
                
                $muzakki = Muzakki::firstOrCreateNormalized($itemMuzakkiData);

                $transaction = null;
                if (!empty($item['id'])) {
                    $transaction = ZakatTransaction::withTrashed()->find($item['id']);
                }

                $txData = [
                    'no_transaksi' => $noTransaksi,
                    'muzakki_id' => $muzakki->id,
                    'category' => $category,
                    'tahun_zakat' => $tahunZakat,
                    'metode' => $metode,
                    'nominal_uang' => $nominalUang,
                    'jumlah_beras_kg' => $jumlahBerasKg,
                    'jiwa' => $item['jiwa'] ?? $data['jiwa'] ?? null,
                    'hari' => $item['hari'] ?? $data['hari'] ?? null,
                    'is_khusus' => $isKhusus,
                    'default_fitrah_cash_per_jiwa_used' => $defaultFitrah > 0 ? $defaultFitrah : null,
                    'default_fidyah_per_hari_used' => $defaultFidyah > 0 ? $defaultFidyah : null,
                    'petugas_id' => $petugasId, // Always server-side — never trust client-submitted petugas_id
                    'waktu_terima' => $waktuTerima, // always use the parsed & normalized Carbon instance
                    'shift' => $data['shift'] ?? ZakatTransaction::SHIFT_PAGI,
                    'keterangan' => $data['keterangan'] ?? null,
                    'is_transfer' => ($metode === ZakatTransaction::METHOD_UANG) ? ($item['is_transfer'] ?? false) : false,
                    'status' => ZakatTransaction::STATUS_VALID,
                    'pembayar_nama' => $pembayarData['muzakki_name'],
                    'pembayar_alamat' => $pembayarData['muzakki_address'],
                    'pembayar_phone' => $pembayarData['muzakki_phone'],
                ];

                if ($transaction) {
                    if ($transaction->trashed()) $transaction->restore();
                    $transaction->update($txData);
                } else {
                    $transaction = ZakatTransaction::create($txData);
                }

                $newIds[] = $transaction->id;
                $results[] = $transaction;
            }

            $idsToDelete = array_diff($existingIds, $newIds);
            $summary = [
                'added' => count($newIds) - count(array_intersect($existingIds, $newIds)),
                'updated' => count(array_intersect($existingIds, $newIds)),
                'removed' => count($idsToDelete),
            ];

            if (!empty($idsToDelete)) {
                ZakatTransaction::whereIn('id', $idsToDelete)->delete(); // Use Soft Delete
            }

            $action = $noTransaksiOverride ? 'Updated.Transaction' : 'Created.Transaction';
            Audit::log(request(), $action, null, [
                'no_transaksi' => $noTransaksi,
                'pembayar' => $pembayarData['muzakki_name'],
                'summary' => $summary,
                'totals' => [
                    'old' => ['uang' => $oldUang, 'beras' => $oldBeras],
                    'new' => ['uang' => (int) collect($results)->sum('nominal_uang'), 'beras' => (float) collect($results)->sum('jumlah_beras_kg')],
                ]
            ]);

            return $results;
        } finally {
            if (isset($lock)) {
                $lock->release();
            }
        }
        });

        $newUang = collect($syncResults)->sum('nominal_uang');
        $newBeras = collect($syncResults)->sum('jumlah_beras_kg');
        $isNominalChanged = (int)$oldUang !== (int)$newUang || abs((float)$oldBeras - (float)$newBeras) > 0.001;

        if (count($syncResults) > 0 && ($noTransaksiOverride === null || $isNominalChanged)) {
            try {
                event(new ZakatTransactionCreated(new Collection($syncResults)));
            } catch (\Throwable $e) {
                // We log the error but let the request succeed because data is already persisted.
                Log::error('Gagal broadcast transaksi: ' . $e->getMessage());
            }
        }

        return $syncResults;
    }

    private function parseWaktuTerima(?string $input, ?string $noTransaksiOverride = null): Carbon
    {
        if ($input) {
            return Carbon::parse($input, 'Asia/Jakarta')->startOfMinute();
        }

        if ($noTransaksiOverride) {
            $existing = ZakatTransaction::where('no_transaksi', $noTransaksiOverride)->value('waktu_terima');
            if ($existing) return Carbon::parse($existing, 'Asia/Jakarta')->startOfMinute();
        }

        return now('Asia/Jakarta')->startOfMinute();
    }

    private function getAnnualDefaults(int $year): array
    {
        if (isset($this->annualCache[$year])) return $this->annualCache[$year];

        $annual = AnnualSetting::where('year', $year)->first();
        $defaults = [
            (int) ($annual?->default_fitrah_cash_per_jiwa ?? 0),
            (int) ($annual?->default_fidyah_per_hari ?? 0),
            (float) ($annual?->default_fitrah_beras_per_jiwa ?? 2.5),
            (float) ($annual?->default_fidyah_beras_per_hari ?? 0.75)
        ];

        $this->annualCache[$year] = $defaults;
        return $defaults;
    }

    public function validateNominalDefaults(array $data): void
    {
        $tahun = (int) ($data['tahun_zakat'] ?? now()->year);
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [$data];
        
        [$defaultFitrah, $defaultFidyah] = $this->getAnnualDefaults($tahun);
        $errors = [];

        foreach ($items as $index => $item) {
            $metode = $item['metode'] ?? null;
            if (!$metode || $metode === ZakatTransaction::METHOD_BERAS) continue;

            $nominal = $item['nominal_uang'] ?? null;
            if ($nominal !== null && $nominal !== '') continue;

            $category = $item['category'] ?? null;
            $hasDefault = ($category === ZakatTransaction::CATEGORY_FITRAH) ? $defaultFitrah > 0 : ($category === ZakatTransaction::CATEGORY_FIDYAH ? $defaultFidyah > 0 : false);

            if (!$hasDefault) {
                $field = isset($data['items']) ? "items.{$index}.nominal_uang" : 'nominal_uang';
                $errors[$field][] = 'Nominal uang wajib diisi karena tidak ada nilai default untuk tahun ' . $tahun;
            }
        }

        if (!empty($errors)) throw \Illuminate\Validation\ValidationException::withMessages($errors);
    }


    private function generateNoTransaksi(Carbon $time): string
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
            
        $seq = ($last && preg_match('/(\d{4})$/', $last, $matches)) ? (int) $matches[1] + 1 : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    private function determineIsKhusus(array $data, int $defaultFitrah, int $defaultFidyah, float $defaultBerasKg, float $defaultFidyahBeras): bool
    {
        $category = $data['category'];
        if ($data['metode'] === ZakatTransaction::METHOD_BERAS) {
            $val = $data['jumlah_beras_kg'] ?? null;
            if ($val === null || $val === '') return false;
            
            if ($category === ZakatTransaction::CATEGORY_FITRAH && $defaultBerasKg > 0) {
                return abs((float)$val - (((int)($data['jiwa']??1)) * $defaultBerasKg)) > 0.001;
            }
            if ($category === ZakatTransaction::CATEGORY_FIDYAH && $defaultFidyahBeras > 0) {
                return abs((float)$val - (((int)($data['hari']??0)) * $defaultFidyahBeras)) > 0.001;
            }
            return false;
        }

        $val = $data['nominal_uang'] ?? null;
        if ($val === null || $val === '') return false;
        if ($category === ZakatTransaction::CATEGORY_FITRAH && $defaultFitrah > 0) return (int)$val !== (((int)($data['jiwa']??1)) * $defaultFitrah);
        if ($category === ZakatTransaction::CATEGORY_FIDYAH && $defaultFidyah > 0) return (int)$val !== (((int)($data['hari']??0)) * $defaultFidyah);
        return false;
    }

    private function executeWithRetry(\Closure $callback)
    {
        $attempts = 0;
        while ($attempts < self::NO_TRANSAKSI_RETRY_ATTEMPTS) {
            $attempts++;
            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                // Retry on unique constraint collision or deadlock
                if ($e->getCode() === '40001' || $e->errorInfo[1] === 1213) continue;
                
                throw $e;
            } catch (\RuntimeException $e) {
                if (str_contains($e->getMessage(), 'Nomor Transaksi') && str_contains($e->getMessage(), 'sudah terpakai')) {
                    continue; 
                }
                throw $e;
            }
        }
        throw new \RuntimeException("Gagal memproses transaksi setelah beberapa kali percobaan karena kepadatan trafik. Silakan klik simpan sekali lagi.");
    }
}

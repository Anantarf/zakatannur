<?php

namespace App\Services;

use App\Models\AnnualSetting;
use App\Models\Muzakki;
use App\Models\ZakatTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Support\Format;
use App\Support\Audit;

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
        
        // Track stats for change detection outside the retry loop
        $oldUang = 0;
        $oldBeras = 0;
        $isNominalChanged = false;

        $syncResults = $this->executeWithRetry(function () use ($data, $items, $petugasId, $waktuTerima, $noTransaksiOverride, &$oldUang, &$oldBeras) {
            $lockName = 'sync_tx_' . $waktuTerima->format('Ymd');
            try {
                if (DB::getDriverName() === 'mysql') {
                    $locked = DB::selectOne("SELECT GET_LOCK('{$lockName}', 10) as locked");
                    if (!$locked || ((int)$locked->locked) !== 1) {
                        throw new \RuntimeException("Gagal mendapatkan kunci transaksi setelah menunggu. Silakan coba lagi.");
                    }
                }
                
                $results = [];
                $noTransaksi = $noTransaksiOverride ?? $this->generateNoTransaksi($waktuTerima);

            // CRITICAL Guard: If this is a NEW transaction (no override), ensure number isn't already used.
            // This prevents "Silent Overwrites" where a new input accidentally hijacks someone else's number.
            if (!$noTransaksiOverride) {
                if (ZakatTransaction::where('no_transaksi', $noTransaksi)->exists()) {
                    throw new \RuntimeException("Nomor Transaksi {$noTransaksi} sudah terpakai. Sila klik simpan sekali lagi untuk mendapatkan nomor baru.");
                }
            }

            // Calculate existing totals BEFORE changes, to detect nominal delta for broadcast.
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

            $this->findOrCreateMuzakki($pembayarData);

            // For EDIT: guard against changing the payer to a completely different person.
            $existingMainTx = $noTransaksiOverride
                ? ZakatTransaction::where('no_transaksi', $noTransaksi)->first()
                : null;

            if ($existingMainTx) {
                $oldNameShort = strtolower(substr(trim($existingMainTx->pembayar_nama), 0, 4));
                $newNameShort = strtolower(substr(trim($pembayarData['muzakki_name']), 0, 4));
                if ($oldNameShort !== $newNameShort) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'pembayar_nama' => "Data tidak dapat diubah menjadi orang lain ({$existingMainTx->pembayar_nama}). Gunakan input transaksi baru untuk orang yang berbeda."
                    ]);
                }
            }

            // Get existing IDs for this transaction number to track deletions
            $existingIds = ZakatTransaction::where('no_transaksi', $noTransaksi)->pluck('id')->toArray();
            $newIds = [];

            foreach ($items as $item) {
                $category = $item['category'] ?? $data['category'] ?? null;
                $metode = $item['metode'] ?? $data['metode'] ?? null;

                if (!$category || !$metode) continue;

                $tahunZakat = (int) ($item['tahun_zakat'] ?? $data['tahun_zakat'] ?? $waktuTerima->year);
                [$defaultFitrah, $defaultFidyah, $defaultBerasKg, $defaultFidyahBeras] = $this->getAnnualDefaults($tahunZakat);

                $itemForComputation = array_merge($item, ['category' => $category, 'metode' => $metode, 'tahun_zakat' => $tahunZakat]);
                $nominalUang = $this->computeNominalUang($itemForComputation, $defaultFitrah, $defaultFidyah);
                $jumlahBerasKg = $this->computeJumlahBerasKg($itemForComputation, $defaultBerasKg, $defaultFidyahBeras);
                $isKhusus = $this->determineIsKhusus($itemForComputation, $defaultFitrah, $defaultFidyah, $defaultBerasKg, $defaultFidyahBeras);

                $itemMuzakkiData = [
                    'muzakki_name'    => $item['muzakki_name'] ?? $pembayarData['muzakki_name'],
                    'muzakki_phone'   => $item['muzakki_phone'] ?? $pembayarData['muzakki_phone'] ?? '',
                    'muzakki_address' => $item['muzakki_address'] ?? $pembayarData['muzakki_address'] ?? '',
                ];
                
                $muzakki = $this->findOrCreateMuzakki($itemMuzakkiData);

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
                    'petugas_id' => $item['petugas_id'] ?? $data['petugas_id'] ?? $petugasId,
                    'waktu_terima' => $item['waktu_terima'] ?? $data['waktu_terima'] ?? now(),
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
                ZakatTransaction::whereIn('id', $idsToDelete)->forceDelete();
            }

            // Consolidate Audit Log
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
            if (DB::getDriverName() === 'mysql') {
                DB::statement("SELECT RELEASE_LOCK('{$lockName}')");
            }
        }
        });

        // Broadcast check after successful commit
        $newUang = collect($syncResults)->sum('nominal_uang');
        $newBeras = collect($syncResults)->sum('jumlah_beras_kg');
        $isNominalChanged = (int)$oldUang !== (int)$newUang || abs((float)$oldBeras - (float)$newBeras) > 0.001;

        if (count($syncResults) > 0 && ($noTransaksiOverride === null || $isNominalChanged)) {
            try {
                event(new \App\Events\ZakatTransactionCreated(new \Illuminate\Database\Eloquent\Collection($syncResults)));
            } catch (\Throwable $e) {
                // We log the error but let the request succeed because data is already persisted.
                \Illuminate\Support\Facades\Log::error('Gagal broadcast transaksi: ' . $e->getMessage());
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

    private function computeNominalUang(array $data, int $defaultFitrah, int $defaultFidyah): ?int
    {
        if ($data['metode'] === ZakatTransaction::METHOD_BERAS) return null;
        if (isset($data['nominal_uang']) && $data['nominal_uang'] !== '') return (int) $data['nominal_uang'];

        if ($data['category'] === ZakatTransaction::CATEGORY_FITRAH && $defaultFitrah > 0) return ((int) ($data['jiwa'] ?? 1)) * $defaultFitrah;
        if ($data['category'] === ZakatTransaction::CATEGORY_FIDYAH && $defaultFidyah > 0) return ((int) ($data['hari'] ?? 0)) * $defaultFidyah;

        return null;
    }

    private function computeJumlahBerasKg(array $data, float $defaultBerasKg, float $defaultFidyahBeras): ?float
    {
        if (isset($data['jumlah_beras_kg']) && $data['jumlah_beras_kg'] !== null && $data['jumlah_beras_kg'] !== '') return (float) $data['jumlah_beras_kg'];

        if ($data['category'] === ZakatTransaction::CATEGORY_FITRAH && $data['metode'] === ZakatTransaction::METHOD_BERAS) {
            return round(((int) ($data['jiwa'] ?? 1)) * $defaultBerasKg, 2);
        }
        
        if ($data['category'] === ZakatTransaction::CATEGORY_FIDYAH && $data['metode'] === ZakatTransaction::METHOD_BERAS) {
            return round(((int) ($data['hari'] ?? 0)) * $defaultFidyahBeras, 2);
        }

        return null;
    }

    private function findOrCreateMuzakki(array $data): Muzakki
    {
        // LEAN: Auto-normalize data to prevent duplicates from typos/spaces
        $name = trim(preg_replace('/\s+/', ' ', (string) $data['muzakki_name']));

        $phone = preg_replace('/[^0-9]/', '', (string) ($data['muzakki_phone'] ?? ''));
        $address = trim((string) ($data['muzakki_address'] ?? ''));

        // Attempt match by name+phone if phone exists, otherwise name+address
        $criteria = ($phone !== '') 
            ? ['name' => $name, 'phone' => $phone] 
            : ['name' => $name, 'address' => $address];

        $muzakki = Muzakki::withTrashed()->updateOrCreate($criteria, [
            'address' => $address,
            'phone'   => $phone
        ]);
        if ($muzakki->trashed()) $muzakki->restore();

        return $muzakki;
    }

    private function generateNoTransaksi(Carbon $time): string
    {
        $prefix = 'TRX-' . $time->format('Ymd') . '-';
        // Note: Lock is now handled in syncTransactions for better coverage
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
                // Retry if unique constraint collision on no_transaksi (Integrity constraint violation: 23000)
                if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'no_transaksi')) continue;
                // Retry if Deadlock (SQLSTATE: 40001 or Error Code: 1213)
                if ($e->getCode() === '40001' || $e->errorInfo[1] === 1213) continue;
                
                throw $e;
            } catch (\RuntimeException $e) {
                // If it's a collision on custom number generation logic, allow silent retry instead of 500
                if (str_contains($e->getMessage(), 'Nomor Transaksi') && str_contains($e->getMessage(), 'sudah terpakai')) {
                    continue; 
                }
                throw $e;
            }
        }
        throw new \RuntimeException("Gagal memproses transaksi setelah beberapa kali percobaan karena kepadatan trafik. Silakan klik simpan sekali lagi.");
    }
}

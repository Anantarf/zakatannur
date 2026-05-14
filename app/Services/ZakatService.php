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

        $syncResult = $this->executeWithRetry(
            fn() => $this->performSync($data, $items, $petugasId, $waktuTerima, $noTransaksiOverride)
        );

        $syncResults = $syncResult['results'];
        $oldUang = $syncResult['oldUang'];
        $oldBeras = $syncResult['oldBeras'];

        $newUang = collect($syncResults)->sum('nominal_uang');
        $newBeras = collect($syncResults)->sum('jumlah_beras_kg');
        $isNominalChanged = (int)$oldUang !== (int)$newUang || abs((float)$oldBeras - (float)$newBeras) > 0.001;

        if (count($syncResults) > 0 && ($noTransaksiOverride === null || $isNominalChanged)) {
            try {
                event(new ZakatTransactionCreated(new Collection($syncResults)));
            } catch (\Throwable $e) {
                Log::error('Gagal broadcast transaksi: ' . $e->getMessage());
            }
        }

        return $syncResults;
    }

    /**
     * Synchronizes a batch of transactions under a daily lock.
     *
     * This method is responsible for the full write flow: generating or reusing
     * the transaction number, persisting the payer data, processing each item,
     * deleting removed rows, and writing the audit log.
     *
     * @return array{results: array<int, ZakatTransaction>, oldUang: int, oldBeras: float}
     */
    private function performSync(array $data, array $items, int $petugasId, Carbon $waktuTerima, ?string $noTransaksiOverride): array
    {
        $lockName = 'sync_tx_' . $waktuTerima->format('Ymd');
        $lock = Cache::lock($lockName, (int) config('zakat.cache.lock_timeout_seconds', 30));

        try {
            if (!$lock->get()) {
                throw new \RuntimeException("Gagal mendapatkan kunci transaksi setelah menunggu (Lock: {$lockName}). Silakan coba lagi.");
            }

            $noTransaksi = $noTransaksiOverride ?? $this->generateNoTransaksi($waktuTerima);

            if (!$noTransaksiOverride && ZakatTransaction::where('no_transaksi', $noTransaksi)->exists()) {
                throw new \RuntimeException("Nomor Transaksi {$noTransaksi} sudah terpakai. Sila klik simpan sekali lagi untuk mendapatkan nomor baru.");
            }

            $oldTotals = $this->getExistingTransactionTotals($noTransaksi);
            $pembayarData = $this->buildPayerData($data);
            Muzakki::firstOrCreateNormalized($pembayarData);

            $existingIds = ZakatTransaction::where('no_transaksi', $noTransaksi)->pluck('id')->toArray();
            [$results, $newIds] = $this->processItems($items, $data, $pembayarData, $petugasId, $waktuTerima, $noTransaksi);

            $idsToDelete = $this->deleteRemovedTransactions($existingIds, $newIds);
            $summary = $this->buildSyncSummary($existingIds, $newIds, $idsToDelete);

            $this->logSyncAudit(
                request(),
                $noTransaksi,
                $pembayarData['muzakki_name'],
                $summary,
                $oldTotals,
                $results,
                $noTransaksiOverride !== null
            );

            return ['results' => $results, 'oldUang' => (int) $oldTotals['uang'], 'oldBeras' => (float) $oldTotals['beras']];
        } finally {
            if (isset($lock)) $lock->release();
        }
    }

    /**
     * @return array{uang:int,beras:float}
     */
    private function getExistingTransactionTotals(string $noTransaksi): array
    {
        $oldTotals = ZakatTransaction::where('no_transaksi', $noTransaksi)
            ->selectRaw('SUM(nominal_uang) as uang, SUM(jumlah_beras_kg) as beras')
            ->first();

        return [
            'uang' => (int) ($oldTotals->uang ?? 0),
            'beras' => (float) ($oldTotals->beras ?? 0),
        ];
    }

    private function buildPayerData(array $data): array
    {
        return [
            'muzakki_name'    => $data['pembayar_nama'],
            'muzakki_phone'   => $data['pembayar_phone'] ?? '',
            'muzakki_address' => $data['pembayar_alamat'],
        ];
    }

    /**
     * @param array<int, int> $existingIds
     * @param array<int, int> $newIds
     * @return array<int, int>
     */
    private function deleteRemovedTransactions(array $existingIds, array $newIds): array
    {
        $idsToDelete = array_diff($existingIds, $newIds);
        if (!empty($idsToDelete)) {
            ZakatTransaction::whereIn('id', $idsToDelete)->delete();
        }

        return $idsToDelete;
    }

    /**
     * @param array<int, int> $existingIds
     * @param array<int, int> $newIds
     * @param array<int, int> $idsToDelete
     * @return array{added:int,updated:int,removed:int}
     */
    private function buildSyncSummary(array $existingIds, array $newIds, array $idsToDelete): array
    {
        $updatedCount = count(array_intersect($existingIds, $newIds));

        return [
            'added'   => count($newIds) - $updatedCount,
            'updated' => $updatedCount,
            'removed' => count($idsToDelete),
        ];
    }

    /**
     * @param array{uang:int,beras:float} $oldTotals
     * @param array<int, ZakatTransaction> $results
     */
    private function logSyncAudit(Request $request, string $noTransaksi, string $pembayarName, array $summary, array $oldTotals, array $results, bool $isUpdate): void
    {
        Audit::log($request, $isUpdate ? 'Updated.Transaction' : 'Created.Transaction', null, [
            'no_transaksi' => $noTransaksi,
            'pembayar'     => $pembayarName,
            'summary'      => $summary,
            'totals'       => [
                'old' => ['uang' => $oldTotals['uang'], 'beras' => $oldTotals['beras']],
                'new' => ['uang' => (int) collect($results)->sum('nominal_uang'), 'beras' => (float) collect($results)->sum('jumlah_beras_kg')],
            ],
        ]);
    }

    /**
     * Converts one request payload or batch item set into persisted transaksi rows.
     *
     * Each item can override category, metode, tahun zakat, and payer identity.
     * Existing soft-deleted rows are restored and updated so edits preserve IDs.
     *
     * @return array{0: array<int, ZakatTransaction>, 1: array<int, int>}
     */
    private function processItems(array $items, array $data, array $pembayarData, int $petugasId, Carbon $waktuTerima, string $noTransaksi): array
    {
        $results = [];
        $newIds = [];

        foreach ($items as $item) {
            $category = $item['category'] ?? $data['category'] ?? null;
            $metode = $item['metode'] ?? $data['metode'] ?? null;
            if (!$category || !$metode) continue;

            $tahunZakat = (int) ($item['tahun_zakat'] ?? $data['tahun_zakat'] ?? $waktuTerima->year);
            [$defaultFitrah, $defaultFidyah, $defaultBerasKg, $defaultFidyahBeras] = $this->getAnnualDefaults($tahunZakat);

            $itemForComputation = array_merge($item, ['category' => $category, 'metode' => $metode, 'tahun_zakat' => $tahunZakat]);
            $muzakki = Muzakki::firstOrCreateNormalized([
                'muzakki_name'    => $item['muzakki_name'] ?? $pembayarData['muzakki_name'],
                'muzakki_phone'   => $item['muzakki_phone'] ?? $pembayarData['muzakki_phone'] ?? '',
                'muzakki_address' => $item['muzakki_address'] ?? $pembayarData['muzakki_address'] ?? '',
            ]);

            $txData = $this->buildTransactionData(
                $item,
                $data,
                $pembayarData,
                $petugasId,
                $waktuTerima,
                $noTransaksi,
                $muzakki->id,
                $category,
                $metode,
                $tahunZakat,
                $itemForComputation,
                $defaultFitrah,
                $defaultFidyah,
                $defaultBerasKg,
                $defaultFidyahBeras
            );

            $transaction = !empty($item['id']) ? ZakatTransaction::withTrashed()->find($item['id']) : null;
            if ($transaction) {
                if ($transaction->trashed()) $transaction->restore();
                $transaction->update($txData);
            } else {
                $transaction = ZakatTransaction::create($txData);
            }

            $newIds[] = $transaction->id;
            $results[] = $transaction;
        }

        return [$results, $newIds];
    }

    /**
     * Builds the persisted transaction payload for a single item.
     */
    private function buildTransactionData(
        array $item,
        array $data,
        array $pembayarData,
        int $petugasId,
        Carbon $waktuTerima,
        string $noTransaksi,
        int $muzakkiId,
        string $category,
        string $metode,
        int $tahunZakat,
        array $itemForComputation,
        int $defaultFitrah,
        int $defaultFidyah,
        float $defaultBerasKg,
        float $defaultFidyahBeras
    ): array {
        return [
            'no_transaksi'                      => $noTransaksi,
            'muzakki_id'                        => $muzakkiId,
            'category'                          => $category,
            'tahun_zakat'                       => $tahunZakat,
            'metode'                            => $metode,
            'nominal_uang'                      => ZakatTransaction::computeNominalUang($itemForComputation, $defaultFitrah, $defaultFidyah),
            'jumlah_beras_kg'                   => ZakatTransaction::computeJumlahBerasKg($itemForComputation, $defaultBerasKg, $defaultFidyahBeras),
            'jiwa'                              => $item['jiwa'] ?? $data['jiwa'] ?? null,
            'hari'                              => $item['hari'] ?? $data['hari'] ?? null,
            'is_khusus'                         => $this->determineIsKhusus($itemForComputation, $defaultFitrah, $defaultFidyah, $defaultBerasKg, $defaultFidyahBeras),
            'default_fitrah_cash_per_jiwa_used' => $defaultFitrah > 0 ? $defaultFitrah : null,
            'default_fidyah_per_hari_used'      => $defaultFidyah > 0 ? $defaultFidyah : null,
            'petugas_id'                        => $petugasId,
            'waktu_terima'                      => $waktuTerima,
            'shift'                             => $data['shift'] ?? ZakatTransaction::SHIFT_PAGI,
            'keterangan'                        => $data['keterangan'] ?? null,
            'is_transfer'                       => ($metode === ZakatTransaction::METHOD_UANG) ? ($item['is_transfer'] ?? false) : false,
            'status'                            => ZakatTransaction::STATUS_VALID,
            'pembayar_nama'                     => $pembayarData['muzakki_name'],
            'pembayar_alamat'                   => $pembayarData['muzakki_address'],
            'pembayar_phone'                    => $pembayarData['muzakki_phone'],
        ];
    }

    /**
     * Normalizes waktu terima from request input or reuses the existing group timestamp.
     *
     * The timestamp is forced to the configured application timezone and rounded
     * down to the nearest minute to keep receipt grouping deterministic.
     */
    private function parseWaktuTerima(?string $input, ?string $noTransaksiOverride = null): Carbon
    {
        $tz = config('zakat.timezone');
        if ($input) {
            return Carbon::parse($input, $tz)->startOfMinute();
        }

        if ($noTransaksiOverride) {
            $existing = ZakatTransaction::where('no_transaksi', $noTransaksiOverride)->value('waktu_terima');
            if ($existing) return Carbon::parse($existing, $tz)->startOfMinute();
        }

        return now($tz)->startOfMinute();
    }

    /**
     * Loads annual defaults for a given zakat year and caches them per service instance.
     *
     * The values are used for nominal calculations and fallback display defaults.
     *
     * @return array{0:int,1:int,2:float,3:float}
     */
    private function getAnnualDefaults(int $year): array
    {
        if (isset($this->annualCache[$year])) return $this->annualCache[$year];

        $annual = AnnualSetting::where('year', $year)->first();
        $defaults = [
            (int) ($annual?->default_fitrah_cash_per_jiwa ?? config('zakat.annual_defaults.fitrah_cash_per_jiwa', 50000)),
            (int) ($annual?->default_fidyah_per_hari ?? config('zakat.annual_defaults.fidyah_per_hari', 30000)),
            (float) ($annual?->default_fitrah_beras_per_jiwa ?? config('zakat.annual_defaults.fitrah_beras_per_jiwa', 2.5)),
            (float) ($annual?->default_fidyah_beras_per_hari ?? config('zakat.annual_defaults.fidyah_beras_per_hari', 0.75))
        ];

        $this->annualCache[$year] = $defaults;
        return $defaults;
    }

    /**
     * Ensures uang-based transactions have a usable nominal before save.
     *
     * This guards cases where the UI leaves nominal empty but annual defaults
     * are required for fitrah/fidyah calculations.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
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


    /**
     * Generates the next daily transaction number using the TRX-YYYYMMDD-#### format.
     *
     * The prefix resets per day and the sequence is derived from the latest matching
     * row, including trashed rows, so restored records do not break numbering.
     */
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

    /**
     * Determines whether a row should be marked as khsus based on computed defaults.
     *
     * This keeps the persisted flag aligned with the same calculation rules used for
     * nominal and beras generation.
     */
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
        $maxAttempts = (int) config('zakat.transaction.retry_attempts', 5);
        $attempts = 0;
        while ($attempts < $maxAttempts) {
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

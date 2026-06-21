<?php

namespace App\Services;

use App\Models\ZakatTransaction;
use App\Services\Periods\ZakatPeriodResolver;
use App\Services\Transactions\AnnualZakatDefaultsResolver;
use App\Services\Transactions\MuzakkiResolver;
use App\Services\Transactions\TransactionAuditLogger;
use App\Services\Transactions\TransactionNominalValidator;
use App\Services\Transactions\TransactionNumberGenerator;
use App\Services\Transactions\TransactionPayloadBuilder;
use App\Services\Transactions\TransactionReviewAssistantService;
use App\Services\Transactions\TransactionRowPersister;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Events\ZakatTransactionCreated;
use App\Exceptions\DuplicateTransactionNumberException;
use Illuminate\Database\Eloquent\Collection;

class ZakatService
{
    private TransactionNumberGenerator $numberGenerator;
    private TransactionNominalValidator $nominalValidator;
    private TransactionRowPersister $rowPersister;
    private TransactionReviewAssistantService $reviewAssistantService;
    private AnnualZakatDefaultsResolver $defaultsResolver;
    private MuzakkiResolver $muzakkiResolver;
    private TransactionPayloadBuilder $payloadBuilder;
    private TransactionAuditLogger $auditLogger;
    private ZakatPeriodResolver $periodResolver;

    public function __construct(
        TransactionNumberGenerator $numberGenerator,
        TransactionNominalValidator $nominalValidator,
        TransactionRowPersister $rowPersister,
        TransactionReviewAssistantService $reviewAssistantService,
        AnnualZakatDefaultsResolver $defaultsResolver,
        MuzakkiResolver $muzakkiResolver,
        TransactionPayloadBuilder $payloadBuilder,
        TransactionAuditLogger $auditLogger,
        ZakatPeriodResolver $periodResolver,
    ) {
        $this->numberGenerator = $numberGenerator;
        $this->nominalValidator = $nominalValidator;
        $this->rowPersister = $rowPersister;
        $this->reviewAssistantService = $reviewAssistantService;
        $this->defaultsResolver = $defaultsResolver;
        $this->muzakkiResolver = $muzakkiResolver;
        $this->payloadBuilder = $payloadBuilder;
        $this->auditLogger = $auditLogger;
        $this->periodResolver = $periodResolver;
    }

    public function storeTransaction(array $data, int $petugasId, ?string $noTransaksiOverride = null): array
    {
        $waktuTerima = $this->parseWaktuTerima($data['waktu_terima'] ?? null, $noTransaksiOverride);
        return $this->syncTransactions($noTransaksiOverride, $data, $petugasId, $waktuTerima);
    }

    public function syncTransactions(?string $noTransaksiOverride, array $data, int $petugasId, ?Carbon $waktuTerima = null): array
    {
        $waktuTerima = $waktuTerima ?? $this->parseWaktuTerima($data['waktu_terima'] ?? null);
        $items = $this->extractItems($data);

        $this->assertItemsBelongToEditableGroup($items, $noTransaksiOverride);

        $syncResult = $this->executeWithRetry(
            fn() => $this->performSync($data, $items, $petugasId, $waktuTerima, $noTransaksiOverride)
        );

        $syncResults = $syncResult['results'];
        $oldUang = $syncResult['oldUang'];
        $oldBeras = $syncResult['oldBeras'];
        $newUang = collect($syncResults)->sum('nominal_uang');
        $newBeras = collect($syncResults)->sum('jumlah_beras_kg');
        $isNominalChanged = (int)$oldUang !== (int)$newUang || abs((float)$oldBeras - (float)$newBeras) > 0.001;
        $hasSignificantNominalChange = $this->hasSignificantNominalChange((int) $oldUang, (int) $newUang, (float) $oldBeras, (float) $newBeras);

        foreach ($syncResults as $transaction) {
            $transaction->setAttribute('anomaly_context', [
                'updated_after_receipt_printed' => (bool) ($syncResult['wasReceiptPrinted'] && $noTransaksiOverride !== null),
                'significant_nominal_change' => (bool) ($noTransaksiOverride !== null && $hasSignificantNominalChange),
                'old_total_uang' => (int) $oldUang,
                'new_total_uang' => (int) $newUang,
                'old_total_beras' => (float) $oldBeras,
                'new_total_beras' => (float) $newBeras,
            ]);
        }

        $this->reviewAssistantService->syncForTransactions($syncResults);

        if (count($syncResults) > 0 && ($noTransaksiOverride === null || $isNominalChanged)) {
            try {
                event(new ZakatTransactionCreated(new Collection($syncResults)));
            } catch (\Throwable $e) {
                Log::error('Gagal broadcast transaksi: ' . $e->getMessage());
            }
        }

        return $syncResults;
    }

    private function assertItemsBelongToEditableGroup(array $items, ?string $noTransaksiOverride): void
    {
        if ($noTransaksiOverride === null) {
            return;
        }

        $allowedIds = ZakatTransaction::withTrashed()
            ->where('no_transaksi', $noTransaksiOverride)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (empty($allowedIds)) {
            return;
        }

        $errors = [];

        foreach ($items as $index => $item) {
            if (empty($item['id'])) {
                continue;
            }

            if (!in_array((int) $item['id'], $allowedIds, true)) {
                $errors["items.{$index}.id"][] = 'Item transaksi tidak valid untuk kelompok transaksi yang sedang diedit.';
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function extractItems(array $data): array
    {
        return isset($data['items']) && is_array($data['items']) ? $data['items'] : [$data];
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

        // NOTE: Cache::lock only serializes within a single host for file/database drivers.
        // For multi-host deployments, set CACHE_DRIVER=redis in .env so this lock is shared.
        // See .env.example for the full multi-host caveat.
        $lock = Cache::lock($lockName, (int) config('zakat.cache.lock_timeout_seconds', 30));

        try {
            if (!$lock->get()) {
                throw new \RuntimeException("Gagal mendapatkan kunci transaksi setelah menunggu (Lock: {$lockName}). Silakan coba lagi.");
            }

            $noTransaksi = $noTransaksiOverride ?? $this->numberGenerator->generate($waktuTerima);
            $wasReceiptPrinted = ZakatTransaction::withTrashed()
                ->where('no_transaksi', $noTransaksi)
                ->whereNotNull('receipt_printed_at')
                ->exists();

            if (!$noTransaksiOverride && ZakatTransaction::where('no_transaksi', $noTransaksi)->exists()) {
                throw new DuplicateTransactionNumberException("Nomor Transaksi {$noTransaksi} sudah terpakai. Sila klik simpan sekali lagi untuk mendapatkan nomor baru.");
            }

            $oldTotals = $this->getExistingTransactionTotals($noTransaksi);
            $pembayarData = $this->muzakkiResolver->payerData($data);

            $existingIds = ZakatTransaction::where('no_transaksi', $noTransaksi)->pluck('id')->toArray();
            [$results, $newIds] = $this->processItems($items, $data, $pembayarData, $petugasId, $waktuTerima, $noTransaksi);

            $idsToDelete = $this->deleteRemovedTransactions($existingIds, $newIds);
            $summary = $this->buildSyncSummary($existingIds, $newIds, $idsToDelete);

            $this->auditLogger->logSync(
                request(),
                $noTransaksi,
                $pembayarData['muzakki_name'],
                $summary,
                $oldTotals,
                $results,
                $noTransaksiOverride !== null,
                $wasReceiptPrinted
            );

            return [
                'results' => $results,
                'oldUang' => (int) $oldTotals['uang'],
                'oldBeras' => (float) $oldTotals['beras'],
                'wasReceiptPrinted' => $wasReceiptPrinted,
            ];
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
            $itemContext = $this->resolveItemContext($item, $data, $waktuTerima);
            if ($itemContext === null) {
                continue;
            }

            $category = $itemContext['category'];
            $metode = $itemContext['metode'];
            $tahunZakat = $itemContext['tahun_zakat'];
            $period = $this->periodResolver->ensureForYear($tahunZakat);
            $defaults = $this->defaultsResolver->resolve($tahunZakat);

            $itemForComputation = $itemContext['item_for_computation'];
            $muzakki = $this->muzakkiResolver->resolveItem($item, $pembayarData);

            $txData = $this->payloadBuilder->build(
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
                $defaults,
                $period
            );

            $transaction = $this->rowPersister->persist($item, $txData);

            $newIds[] = $transaction->id;
            $results[] = $transaction;
        }

        return [$results, $newIds];
    }

    private function resolveItemContext(array $item, array $data, Carbon $waktuTerima): ?array
    {
        $category = $item['category'] ?? $data['category'] ?? null;
        $metode = $item['metode'] ?? $data['metode'] ?? null;

        if (!$category || !$metode) {
            return null;
        }

        $tahunZakat = (int) ($item['tahun_zakat'] ?? $data['tahun_zakat'] ?? $waktuTerima->year);

        return [
            'category' => $category,
            'metode' => $metode,
            'tahun_zakat' => $tahunZakat,
            'item_for_computation' => array_merge($item, [
                'category' => $category,
                'metode' => $metode,
                'tahun_zakat' => $tahunZakat,
            ]),
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
        $items = $this->extractItems($data);

        [$defaultFitrah, $defaultFidyah, $defaultFitrahBeras, $defaultFidyahBeras] = $this->defaultsResolver
            ->resolve($tahun)
            ->toTuple();

        $this->nominalValidator->validate(
            $data,
            $items,
            $tahun,
            $defaultFitrah,
            $defaultFidyah,
            $defaultFitrahBeras,
            $defaultFidyahBeras
        );
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
            } catch (DuplicateTransactionNumberException) {
                continue;
            } catch (\RuntimeException $e) {
                throw $e;
            }
        }
        throw new \RuntimeException("Gagal memproses transaksi setelah beberapa kali percobaan karena kepadatan trafik. Silakan klik simpan sekali lagi.");
    }

    private function hasSignificantNominalChange(int $oldUang, int $newUang, float $oldBeras, float $newBeras): bool
    {
        $uangDelta = abs($newUang - $oldUang);
        $berasDelta = abs($newBeras - $oldBeras);

        $uangThreshold = max(50000, (int) round(max($oldUang, $newUang) * 0.5));
        $berasThreshold = max(2.5, max($oldBeras, $newBeras) * 0.5);

        return $uangDelta >= $uangThreshold || $berasDelta >= $berasThreshold;
    }
}
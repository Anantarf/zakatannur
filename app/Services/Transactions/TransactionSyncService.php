<?php

namespace App\Services\Transactions;

use App\Exceptions\DuplicateTransactionNumberException;
use App\Models\ZakatTransaction;
use App\Services\Periods\ZakatPeriodResolver;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TransactionSyncService
{
    public function __construct(
        private TransactionNumberGenerator $numberGenerator,
        private TransactionRowPersister $rowPersister,
        private AnnualZakatDefaultsResolver $defaultsResolver,
        private MuzakkiResolver $muzakkiResolver,
        private TransactionPayloadBuilder $payloadBuilder,
        private TransactionAuditLogger $auditLogger,
        private ZakatPeriodResolver $periodResolver,
        private TransactionLockManager $lockManager,
    ) {
    }

    public function sync(array $data, array $items, int $petugasId, Carbon $waktuTerima, ?string $noTransaksiOverride): TransactionSyncResult
    {
        $lockName = 'sync_tx_' . $waktuTerima->format('Ymd');

        return $this->executeWithRetry(
            fn () => $this->performSync($data, $items, $petugasId, $waktuTerima, $noTransaksiOverride),
            $lockName
        );
    }

    private function performSync(array $data, array $items, int $petugasId, Carbon $waktuTerima, ?string $noTransaksiOverride): TransactionSyncResult
    {
        $noTransaksi = $noTransaksiOverride ?? $this->numberGenerator->generate($waktuTerima);
        $wasReceiptPrinted = ZakatTransaction::withTrashed()
            ->where('no_transaksi', $noTransaksi)
            ->whereNotNull('receipt_printed_at')
            ->exists();

        if (!$noTransaksiOverride && ZakatTransaction::withTrashed()->where('no_transaksi', $noTransaksi)->exists()) {
            throw new DuplicateTransactionNumberException("Nomor Transaksi {$noTransaksi} sudah terpakai. Sila klik simpan sekali lagi untuk mendapatkan nomor baru.");
        }

        $oldTotals = $this->getExistingTransactionTotals($noTransaksi);
        $pembayarData = $this->muzakkiResolver->payerData($data);

        $existingIds = ZakatTransaction::where('no_transaksi', $noTransaksi)->pluck('id')->toArray();
        [$transactions, $newIds] = $this->processItems($items, $data, $pembayarData, $petugasId, $waktuTerima, $noTransaksi);

        $idsToDelete = $this->deleteRemovedTransactions($existingIds, $newIds);
        $summary = $this->buildSyncSummary($existingIds, $newIds, $idsToDelete);

        $this->auditLogger->logSync(
            request(),
            $noTransaksi,
            $pembayarData['muzakki_name'],
            $summary,
            $oldTotals,
            $transactions,
            $noTransaksiOverride !== null,
            $wasReceiptPrinted
        );

        return new TransactionSyncResult(
            $transactions,
            (int) $oldTotals['uang'],
            (float) $oldTotals['beras'],
            $wasReceiptPrinted
        );
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
            'added' => count($newIds) - $updatedCount,
            'updated' => $updatedCount,
            'removed' => count($idsToDelete),
        ];
    }

    /**
     * @return array{0: array<int, ZakatTransaction>, 1: array<int, int>}
     */
    private function processItems(array $items, array $data, array $pembayarData, int $petugasId, Carbon $waktuTerima, string $noTransaksi): array
    {
        $transactions = [];
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

            $muzakki = $this->muzakkiResolver->resolveItem($item, $pembayarData);

            $transactionData = $this->payloadBuilder->build(
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
                $itemContext['item_for_computation'],
                $defaults,
                $period
            );

            $transaction = $this->rowPersister->persist($item, $transactionData);

            $newIds[] = $transaction->id;
            $transactions[] = $transaction;
        }

        return [$transactions, $newIds];
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

    private function executeWithRetry(\Closure $callback, ?string $lockName = null): TransactionSyncResult
    {
        $maxAttempts = (int) config('zakat.transaction.retry_attempts', 5);
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $attempts++;
            $lockToken = $lockName ? $this->lockManager->acquire($lockName) : null;

            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                if ($e->getCode() === '40001' || ($e->errorInfo[1] ?? null) === 1213) {
                    continue;
                }

                throw $e;
            } catch (DuplicateTransactionNumberException) {
                continue;
            } finally {
                $this->lockManager->release($lockToken);
            }
        }

        throw new \RuntimeException('Gagal memproses transaksi setelah beberapa kali percobaan karena kepadatan trafik. Silakan klik simpan sekali lagi.');
    }
}

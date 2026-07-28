<?php

namespace App\Services;

use App\Models\ZakatTransaction;
use App\Services\Transactions\AnnualZakatDefaultsResolver;
use App\Services\Transactions\TransactionNominalValidator;
use App\Services\Transactions\TransactionReviewAssistantService;
use App\Services\Transactions\TransactionSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Events\ZakatTransactionCreated;
use Illuminate\Database\Eloquent\Collection;

class ZakatService
{
    private TransactionNominalValidator $nominalValidator;
    private TransactionReviewAssistantService $reviewAssistantService;
    private AnnualZakatDefaultsResolver $defaultsResolver;
    private TransactionSyncService $syncService;

    public function __construct(
        TransactionNominalValidator $nominalValidator,
        TransactionReviewAssistantService $reviewAssistantService,
        AnnualZakatDefaultsResolver $defaultsResolver,
        TransactionSyncService $syncService,
    ) {
        $this->nominalValidator = $nominalValidator;
        $this->reviewAssistantService = $reviewAssistantService;
        $this->defaultsResolver = $defaultsResolver;
        $this->syncService = $syncService;
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

        $syncResult = $this->syncService->sync($data, $items, $petugasId, $waktuTerima, $noTransaksiOverride);
        $syncResults = $syncResult->transactions;
        $newUang = $syncResult->newUang();
        $newBeras = $syncResult->newBeras();
        $hasSignificantNominalChange = $this->hasSignificantNominalChange($syncResult->oldUang, $newUang, $syncResult->oldBeras, $newBeras);

        foreach ($syncResults as $transaction) {
            $transaction->setAttribute('anomaly_context', [
                'updated_after_receipt_printed' => (bool) ($syncResult->wasReceiptPrinted && $noTransaksiOverride !== null),
                'significant_nominal_change' => (bool) ($noTransaksiOverride !== null && $hasSignificantNominalChange),
                'old_total_uang' => $syncResult->oldUang,
                'new_total_uang' => (int) $newUang,
                'old_total_beras' => $syncResult->oldBeras,
                'new_total_beras' => (float) $newBeras,
            ]);
        }

        $this->reviewAssistantService->syncForTransactions($syncResults);

        if (count($syncResults) > 0 && ($noTransaksiOverride === null || $syncResult->nominalChanged())) {
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
     * @throws ValidationException
     */
    public function validateNominalDefaults(array $data, bool $requireActiveYear = false): void
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
            $defaultFidyahBeras,
            $requireActiveYear
        );
    }

    private function hasSignificantNominalChange(int $oldUang, int $newUang, float $oldBeras, float $newBeras): bool
    {
        $uangDelta = abs($newUang - $oldUang);
        $berasDelta = abs($newBeras - $oldBeras);

        $pct = (float) config('zakat.thresholds.significant_change_percent', 0.5);
        $berasPct = (float) config('zakat.thresholds.significant_change_beras_percent', 0.5);
        $uangThreshold = max(50000, (int) round(max($oldUang, $newUang) * $pct));
        $berasThreshold = max(2.5, max($oldBeras, $newBeras) * $berasPct);

        return $uangDelta >= $uangThreshold || $berasDelta >= $berasThreshold;
    }
}

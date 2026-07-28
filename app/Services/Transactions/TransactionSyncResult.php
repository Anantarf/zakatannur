<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;

class TransactionSyncResult
{
    /**
     * @var array<int, ZakatTransaction>
     */
    public array $transactions;
    public int $oldUang;
    public float $oldBeras;
    public bool $wasReceiptPrinted;

    /**
     * @param array<int, ZakatTransaction> $transactions
     */
    public function __construct(
        array $transactions,
        int $oldUang,
        float $oldBeras,
        bool $wasReceiptPrinted
    ) {
        $this->transactions = $transactions;
        $this->oldUang = $oldUang;
        $this->oldBeras = $oldBeras;
        $this->wasReceiptPrinted = $wasReceiptPrinted;
    }

    public function newUang(): int
    {
        return (int) collect($this->transactions)->sum('nominal_uang');
    }

    public function newBeras(): float
    {
        return (float) collect($this->transactions)->sum('jumlah_beras_kg');
    }

    public function nominalChanged(): bool
    {
        return $this->oldUang !== $this->newUang()
            || abs($this->oldBeras - $this->newBeras()) > 0.001;
    }
}

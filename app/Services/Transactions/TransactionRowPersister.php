<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;

class TransactionRowPersister
{
    public function persist(array $item, array $transactionData): ZakatTransaction
    {
        $transaction = !empty($item['id']) ? ZakatTransaction::withTrashed()->find($item['id']) : null;

        if ($transaction) {
            if ($transaction->trashed()) {
                $transaction->restore();
            }

            $transaction->update($transactionData);

            return $transaction;
        }

        return ZakatTransaction::create($transactionData);
    }
}

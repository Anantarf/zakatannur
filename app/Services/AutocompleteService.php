<?php

namespace App\Services;

use App\Models\ZakatTransaction;

class AutocompleteService
{
    private const AVAILABLE_TYPES = ['pembayar_name', 'penerima_name', 'category', 'no_transaksi'];
    private const DEFAULT_TYPES = ['pembayar_name', 'penerima_name', 'category', 'no_transaksi'];

    public static function getAutocompleteData(array $types = []): array
    {
        $typesToFetch = empty($types) ? self::DEFAULT_TYPES : $types;
        $result = [];

        foreach ($typesToFetch as $type) {
            if (!in_array($type, self::AVAILABLE_TYPES)) {
                continue;
            }

            $result[$type] = self::fetchUniqueValues($type);
        }

        return $result;
    }

    private static function fetchUniqueValues(string $type): array
    {
        if ($type === 'pembayar_name') {
            return ZakatTransaction::query()
                ->whereNotNull('pembayar_name')
                ->distinct('pembayar_name')
                ->pluck('pembayar_name')
                ->filter()
                ->values()
                ->toArray();
        }

        if ($type === 'penerima_name') {
            return ZakatTransaction::query()
                ->whereNotNull('penerima_name')
                ->distinct('penerima_name')
                ->pluck('penerima_name')
                ->filter()
                ->values()
                ->toArray();
        }

        if ($type === 'category') {
            return ['Zakat Mal', 'Zakat Fitrah', 'Infaq', 'Shadaqah'];
        }

        if ($type === 'no_transaksi') {
            return ZakatTransaction::query()
                ->select('no_transaksi')
                ->whereNotNull('no_transaksi')
                ->orderByDesc('created_at')
                ->limit(100)
                ->pluck('no_transaksi')
                ->toArray();
        }

        return [];
    }
}

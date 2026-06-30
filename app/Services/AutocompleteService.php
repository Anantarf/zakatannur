<?php

namespace App\Services;

use App\Models\ZakatTransaction;

class AutocompleteService
{
    private const AVAILABLE_TYPES = ['pembayar_nama', 'category', 'no_transaksi'];
    private const DEFAULT_TYPES = ['pembayar_nama', 'category', 'no_transaksi'];

    public static function getAutocompleteData(array $types = [], ?string $category = null): array
    {
        $typesToFetch = empty($types) ? self::DEFAULT_TYPES : $types;
        $result = [];

        foreach ($typesToFetch as $type) {
            if (!in_array($type, self::AVAILABLE_TYPES)) {
                continue;
            }

            $result[$type] = self::fetchUniqueValues($type, $category);
        }

        return $result;
    }

    private static function fetchUniqueValues(string $type, ?string $category = null): array
    {
        if ($type === 'pembayar_nama') {
            $query = ZakatTransaction::query()
                ->whereNotNull('pembayar_nama');

            if ($category) {
                $query->where('category', $category);
            }

            return $query
                ->distinct('pembayar_nama')
                ->pluck('pembayar_nama')
                ->filter()
                ->values()
                ->toArray();
        }

        if ($type === 'category') {
            return ['Zakat Mal', 'Zakat Fitrah', 'Infaq', 'Shadaqah'];
        }

        if ($type === 'no_transaksi') {
            $query = ZakatTransaction::query()
                ->select('no_transaksi')
                ->whereNotNull('no_transaksi');

            if ($category) {
                $query->where('category', $category);
            }

            return $query
                ->orderByDesc('created_at')
                ->limit(100)
                ->pluck('no_transaksi')
                ->toArray();
        }

        return [];
    }
}

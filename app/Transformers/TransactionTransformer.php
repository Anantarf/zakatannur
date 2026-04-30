<?php

namespace App\Transformers;

use App\Models\ZakatTransaction;
use Illuminate\Support\Collection;

class TransactionTransformer
{
    /**
     * Transform a collection of transactions into human-readable format for the AlpineJS form.
     */
    public static function toAlpinePersons(Collection $transactions): array
    {
        $persons = [];
        $cardCounter = 0;

        foreach ($transactions as $item) {
            /** @var ZakatTransaction $item */
            $muzakkiId = $item->muzakki_id;
            $name = $item->muzakki->name ?? '';
            $category = $item->category;

            // Find an existing card for this muzakki that hasn't used this category yet
            $foundIndex = -1;
            foreach ($persons as $idx => $p) {
                if ($p['muzakki_id'] === $muzakkiId && !$p['zakat'][$category]['active']) {
                    $foundIndex = $idx;
                    break;
                }
            }

            if ($foundIndex === -1) {
                // New card needed
                $persons[] = [
                    'id' => (++$cardCounter) . '-' . $muzakkiId,
                    'muzakki_id' => $muzakkiId,
                    'name' => $name,
                    'zakat' => [
                        'fitrah' => ['active' => false, 'metode' => 'uang', 'is_custom' => false, 'is_transfer' => false, 'nominal' => ''],
                        'fidyah' => ['active' => false, 'metode' => 'uang', 'is_custom' => false, 'is_transfer' => false, 'hari' => '', 'nominal' => ''],
                        'mal'   => ['active' => false, 'metode' => 'uang', 'is_transfer' => false, 'nominal' => ''],
                        'infaq' => ['active' => false, 'metode' => 'uang', 'is_transfer' => false, 'nominal' => ''],
                    ]
                ];
                $foundIndex = count($persons) - 1;
            }

            // Populate the selected card
                $persons[$foundIndex]['zakat'][$category] = [
                    'id'        => $item->id,
                    'active'    => true,
                    'metode'    => $item->metode,
                    'is_custom' => (bool) $item->is_khusus,
                    'is_transfer' => (bool) $item->is_transfer,
                    'hari'      => $category === 'fidyah' ? $item->hari : '',
                    'nominal'   => $item->metode === ZakatTransaction::METHOD_BERAS
                                   ? $item->jumlah_beras_kg_display
                                   : $item->nominal_uang_display,
                ];
        }

        return $persons;
    }
}

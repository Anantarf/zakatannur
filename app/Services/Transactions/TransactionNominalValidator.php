<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use Illuminate\Validation\ValidationException;

class TransactionNominalValidator
{
    public function validate(array $data, array $items, int $tahun, int $defaultFitrah, int $defaultFidyah): void
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $metode = $item['metode'] ?? null;
            if (!$metode || $metode === ZakatTransaction::METHOD_BERAS) {
                continue;
            }

            $nominal = $item['nominal_uang'] ?? null;
            if ($nominal !== null && $nominal !== '') {
                continue;
            }

            $category = $item['category'] ?? null;
            $hasDefault = ($category === ZakatTransaction::CATEGORY_FITRAH)
                ? $defaultFitrah > 0
                : ($category === ZakatTransaction::CATEGORY_FIDYAH ? $defaultFidyah > 0 : false);

            if (!$hasDefault) {
                $field = isset($data['items']) ? "items.{$index}.nominal_uang" : 'nominal_uang';
                $errors[$field][] = 'Nominal uang wajib diisi karena tidak ada nilai default untuk tahun ' . $tahun;
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}

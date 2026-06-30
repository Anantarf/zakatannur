<?php

namespace App\Services\Transactions;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use Illuminate\Validation\ValidationException;

class TransactionNominalValidator
{
    /**
     * Validate that the nominal fields on each transaction item are consistent
     * with the configured annual defaults for fitrah and fidyah.
     *
     * The implementation is split into one private rule per (category, metode)
     * pair so each rule stays short and easy to evolve independently.
     */
    public function validate(
        array $data,
        array $items,
        int $tahun,
        int $defaultFitrah,
        int $defaultFidyah,
        float $defaultFitrahBeras,
        float $defaultFidyahBeras,
        bool $requireActiveYear = false
    ): void {
        $errors = [];
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        if ($requireActiveYear && $tahun !== $activeYear) {
            $errors['tahun_zakat'][] = 'Tahun zakat harus mengikuti periode aktif sistem saat ini.';
        }

        foreach ($items as $index => $item) {
            $fieldPrefix = isset($data['items']) ? "items.{$index}" : '';
            $category = $item['category'] ?? $data['category'] ?? null;
            $metode = $item['metode'] ?? $data['metode'] ?? null;
            if (!$category || !$metode) {
                continue;
            }

            $context = $this->buildContext($item, $data, $fieldPrefix);
            $context['jiwa'] = (int) ($item['jiwa'] ?? $data['jiwa'] ?? 0);
            $context['hari'] = (int) ($item['hari'] ?? $data['hari'] ?? 0);

            $errors = match (true) {
                $category === ZakatTransaction::CATEGORY_FITRAH && $metode === ZakatTransaction::METHOD_BERAS
                    => $this->validateFitrahBeras($context, $defaultFitrahBeras, $errors),
                $category === ZakatTransaction::CATEGORY_FITRAH
                    => $this->validateFitrahUang($context, $defaultFitrah, $tahun, $errors),
                $category === ZakatTransaction::CATEGORY_FIDYAH && $metode === ZakatTransaction::METHOD_BERAS
                    => $this->validateFidyahBeras($context, $defaultFidyahBeras, $errors),
                $category === ZakatTransaction::CATEGORY_FIDYAH
                    => $this->validateFidyahUang($context, $defaultFidyah, $tahun, $errors),
                $metode === ZakatTransaction::METHOD_BERAS
                    => $this->validateDefaultBeras($context, $errors),
                default
                    => $this->validateDefaultUang($context, $errors),
            };
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array{prefix: string, nominal: mixed, nominalProvided: bool, nominalInt: ?int, beras: mixed, berasProvided: bool, berasFloat: ?float}
     */
    private function buildContext(array $item, array $data, string $fieldPrefix): array
    {
        $nominal = $item['nominal_uang'] ?? $data['nominal_uang'] ?? null;
        $beras = $item['jumlah_beras_kg'] ?? $data['jumlah_beras_kg'] ?? null;
        $nominalProvided = $nominal !== null && $nominal !== '';
        $berasProvided = $beras !== null && $beras !== '';

        return [
            'prefix' => $fieldPrefix,
            'nominal' => $nominal,
            'nominalProvided' => $nominalProvided,
            'nominalInt' => $nominalProvided ? (int) $nominal : null,
            'beras' => $beras,
            'berasProvided' => $berasProvided,
            'berasFloat' => $berasProvided ? (float) $beras : null,
            'jiwa' => 0,
            'hari' => 0,
        ];
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function validateFitrahBeras(array $c, float $defaultFitrahBeras, array $errors): array
    {
        if ($c['jiwa'] < 1) {
            $errors[$this->fieldName($c['prefix'], 'jiwa')][] = 'Zakat fitrah wajib memiliki jumlah jiwa minimal 1.';
        }
        if ($c['hari'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'hari')][] = 'Field hari tidak berlaku untuk zakat fitrah.';
        }
        if ($c['nominalProvided']) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Metode beras tidak boleh menyimpan nominal uang.';
        }
        if (!$c['berasProvided'] && $defaultFitrahBeras <= 0) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Jumlah beras wajib diisi karena tidak ada standar beras fitrah pada periode aktif.';
        } elseif ($c['berasProvided'] && (float) $c['berasFloat'] <= 0) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Jumlah beras fitrah harus lebih dari 0.';
        }
        return $errors;
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function validateFitrahUang(array $c, int $defaultFitrah, int $tahun, array $errors): array
    {
        if ($c['jiwa'] < 1) {
            $errors[$this->fieldName($c['prefix'], 'jiwa')][] = 'Zakat fitrah wajib memiliki jumlah jiwa minimal 1.';
        }
        if ($c['hari'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'hari')][] = 'Field hari tidak berlaku untuk zakat fitrah.';
        }
        if ($c['berasProvided']) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Metode non-beras tidak boleh menyimpan jumlah beras.';
        }
        if (!$c['nominalProvided'] && $defaultFitrah <= 0) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Nominal uang wajib diisi karena tidak ada nilai default untuk tahun ' . $tahun;
        } elseif ($c['nominalProvided'] && (int) $c['nominalInt'] <= 0) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Nominal fitrah harus lebih dari 0.';
        }
        return $errors;
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function validateFidyahBeras(array $c, float $defaultFidyahBeras, array $errors): array
    {
        if ($c['hari'] < 1) {
            $errors[$this->fieldName($c['prefix'], 'hari')][] = 'Fidyah wajib memiliki jumlah hari minimal 1.';
        }
        if ($c['jiwa'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'jiwa')][] = 'Field jiwa tidak berlaku untuk fidyah.';
        }
        if ($c['nominalProvided']) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Metode beras tidak boleh menyimpan nominal uang.';
        }
        if (!$c['berasProvided'] && $defaultFidyahBeras <= 0) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Jumlah beras wajib diisi karena tidak ada standar beras fidyah pada periode aktif.';
        } elseif ($c['berasProvided'] && (float) $c['berasFloat'] <= 0) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Jumlah beras fidyah harus lebih dari 0.';
        }
        return $errors;
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function validateFidyahUang(array $c, int $defaultFidyah, int $tahun, array $errors): array
    {
        if ($c['hari'] < 1) {
            $errors[$this->fieldName($c['prefix'], 'hari')][] = 'Fidyah wajib memiliki jumlah hari minimal 1.';
        }
        if ($c['jiwa'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'jiwa')][] = 'Field jiwa tidak berlaku untuk fidyah.';
        }
        if ($c['berasProvided']) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Metode non-beras tidak boleh menyimpan jumlah beras.';
        }
        if (!$c['nominalProvided'] && $defaultFidyah <= 0) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Nominal uang wajib diisi karena tidak ada nilai default untuk tahun ' . $tahun;
        } elseif ($c['nominalProvided'] && (int) $c['nominalInt'] <= 0) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Nominal fidyah harus lebih dari 0.';
        }
        return $errors;
    }

    /**
     * Mal or Infaq with metode beras: jiwa/hari must be empty, beras must be > 0.
     *
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function validateDefaultBeras(array $c, array $errors): array
    {
        if ($c['jiwa'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'jiwa')][] = 'Field jiwa hanya berlaku untuk zakat fitrah.';
        }
        if ($c['hari'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'hari')][] = 'Field hari hanya berlaku untuk fidyah.';
        }
        if ($c['nominalProvided']) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Metode beras tidak boleh menyimpan nominal uang.';
        }
        if (!$c['berasProvided'] || (float) $c['berasFloat'] <= 0) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Jumlah beras wajib diisi untuk metode beras.';
        }
        return $errors;
    }

    /**
     * Mal or Infaq with metode uang: jiwa/hari must be empty, nominal must be > 0.
     *
     * @param array<string, array<int, string>> $errors
     * @return array<string, array<int, string>>
     */
    private function validateDefaultUang(array $c, array $errors): array
    {
        if ($c['jiwa'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'jiwa')][] = 'Field jiwa hanya berlaku untuk zakat fitrah.';
        }
        if ($c['hari'] > 0) {
            $errors[$this->fieldName($c['prefix'], 'hari')][] = 'Field hari hanya berlaku untuk fidyah.';
        }
        if ($c['berasProvided']) {
            $errors[$this->fieldName($c['prefix'], 'jumlah_beras_kg')][] = 'Metode non-beras tidak boleh menyimpan jumlah beras.';
        }
        if (!$c['nominalProvided'] || (int) $c['nominalInt'] <= 0) {
            $errors[$this->fieldName($c['prefix'], 'nominal_uang')][] = 'Nominal uang wajib diisi untuk transaksi non-beras.';
        }
        return $errors;
    }

    private function fieldName(string $prefix, string $field): string
    {
        return $prefix !== '' ? "{$prefix}.{$field}" : $field;
    }
}

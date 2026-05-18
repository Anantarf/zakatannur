<?php

namespace App\Services\Transactions;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use Illuminate\Validation\ValidationException;

class TransactionNominalValidator
{
    public function validate(
        array $data,
        array $items,
        int $tahun,
        int $defaultFitrah,
        int $defaultFidyah,
        float $defaultFitrahBeras,
        float $defaultFidyahBeras
    ): void
    {
        $errors = [];
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        if ($tahun !== $activeYear) {
            $errors['tahun_zakat'][] = 'Tahun zakat harus mengikuti periode aktif sistem saat ini.';
        }

        foreach ($items as $index => $item) {
            $fieldPrefix = isset($data['items']) ? "items.{$index}" : '';
            $category = $item['category'] ?? $data['category'] ?? null;
            $metode = $item['metode'] ?? $data['metode'] ?? null;
            $nominal = $item['nominal_uang'] ?? $data['nominal_uang'] ?? null;
            $beras = $item['jumlah_beras_kg'] ?? $data['jumlah_beras_kg'] ?? null;
            $jiwa = (int) ($item['jiwa'] ?? $data['jiwa'] ?? 0);
            $hari = (int) ($item['hari'] ?? $data['hari'] ?? 0);

            if (!$category || !$metode) {
                continue;
            }

            $isBerasMethod = $metode === ZakatTransaction::METHOD_BERAS;
            $nominalProvided = $nominal !== null && $nominal !== '';
            $berasProvided = $beras !== null && $beras !== '';
            $nominalInt = $nominalProvided ? (int) $nominal : null;
            $berasFloat = $berasProvided ? (float) $beras : null;

            if ($category === ZakatTransaction::CATEGORY_FITRAH) {
                if ($jiwa < 1) {
                    $errors[$this->fieldName($fieldPrefix, 'jiwa')][] = 'Zakat fitrah wajib memiliki jumlah jiwa minimal 1.';
                }
                if ($hari > 0) {
                    $errors[$this->fieldName($fieldPrefix, 'hari')][] = 'Field hari tidak berlaku untuk zakat fitrah.';
                }

                if ($isBerasMethod) {
                    if ($nominalProvided) {
                        $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = 'Metode beras tidak boleh menyimpan nominal uang.';
                    }

                    if (!$berasProvided && $defaultFitrahBeras <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Jumlah beras wajib diisi karena tidak ada standar beras fitrah pada periode aktif.';
                    } elseif ($berasProvided && (float) $berasFloat <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Jumlah beras fitrah harus lebih dari 0.';
                    }
                } else {
                    if ($berasProvided) {
                        $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Metode non-beras tidak boleh menyimpan jumlah beras.';
                    }

                    if (!$nominalProvided && $defaultFitrah <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = $defaultFitrah > 0
                            ? 'Nominal uang wajib diisi dan harus sesuai standar periode aktif.'
                            : 'Nominal uang wajib diisi karena tidak ada nilai default untuk tahun ' . $tahun;
                    } elseif ($nominalProvided && (int) $nominalInt <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = 'Nominal fitrah harus lebih dari 0.';
                    }
                }

                continue;
            }

            if ($category === ZakatTransaction::CATEGORY_FIDYAH) {
                if ($hari < 1) {
                    $errors[$this->fieldName($fieldPrefix, 'hari')][] = 'Fidyah wajib memiliki jumlah hari minimal 1.';
                }
                if ($jiwa > 0) {
                    $errors[$this->fieldName($fieldPrefix, 'jiwa')][] = 'Field jiwa tidak berlaku untuk fidyah.';
                }

                if ($isBerasMethod) {
                    if ($nominalProvided) {
                        $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = 'Metode beras tidak boleh menyimpan nominal uang.';
                    }

                    if (!$berasProvided && $defaultFidyahBeras <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Jumlah beras wajib diisi karena tidak ada standar beras fidyah pada periode aktif.';
                    } elseif ($berasProvided && (float) $berasFloat <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Jumlah beras fidyah harus lebih dari 0.';
                    }
                } else {
                    if ($berasProvided) {
                        $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Metode non-beras tidak boleh menyimpan jumlah beras.';
                    }

                    if (!$nominalProvided && $defaultFidyah <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = $defaultFidyah > 0
                            ? 'Nominal uang wajib diisi dan harus sesuai standar periode aktif.'
                            : 'Nominal uang wajib diisi karena tidak ada nilai default untuk tahun ' . $tahun;
                    } elseif ($nominalProvided && (int) $nominalInt <= 0) {
                        $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = 'Nominal fidyah harus lebih dari 0.';
                    }
                }

                continue;
            }

            if ($jiwa > 0) {
                $errors[$this->fieldName($fieldPrefix, 'jiwa')][] = 'Field jiwa hanya berlaku untuk zakat fitrah.';
            }

            if ($hari > 0) {
                $errors[$this->fieldName($fieldPrefix, 'hari')][] = 'Field hari hanya berlaku untuk fidyah.';
            }

            if ($isBerasMethod) {
                if ($nominalProvided) {
                    $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = 'Metode beras tidak boleh menyimpan nominal uang.';
                }
                if (!$berasProvided || (float) $berasFloat <= 0) {
                    $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Jumlah beras wajib diisi untuk metode beras.';
                }
            } else {
                if ($berasProvided) {
                    $errors[$this->fieldName($fieldPrefix, 'jumlah_beras_kg')][] = 'Metode non-beras tidak boleh menyimpan jumlah beras.';
                }
                if (!$nominalProvided || (int) $nominalInt <= 0) {
                    $errors[$this->fieldName($fieldPrefix, 'nominal_uang')][] = 'Nominal uang wajib diisi untuk transaksi non-beras.';
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function fieldName(string $prefix, string $field): string
    {
        return $prefix !== '' ? "{$prefix}.{$field}" : $field;
    }
}

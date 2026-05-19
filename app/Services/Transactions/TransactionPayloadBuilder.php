<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use App\Models\ZakatPeriod;
use Carbon\Carbon;

class TransactionPayloadBuilder
{
    public function build(
        array $item,
        array $data,
        array $payerData,
        int $petugasId,
        Carbon $waktuTerima,
        string $noTransaksi,
        int $muzakkiId,
        string $category,
        string $metode,
        int $tahunZakat,
        array $itemForComputation,
        AnnualZakatDefaults $defaults,
        ?ZakatPeriod $period = null
    ): array {
        return [
            'no_transaksi' => $noTransaksi,
            'muzakki_id' => $muzakkiId,
            'category' => $category,
            'tahun_zakat' => $tahunZakat,
            'zakat_period_id' => $period?->id,
            'hijri_year' => $period?->hijri_year,
            'hijri_month' => $period?->hijri_month,
            'metode' => $metode,
            'nominal_uang' => ZakatTransaction::computeNominalUang($itemForComputation, $defaults->fitrahCashPerJiwa, $defaults->fidyahPerHari),
            'jumlah_beras_kg' => ZakatTransaction::computeJumlahBerasKg($itemForComputation, $defaults->fitrahBerasPerJiwa, $defaults->fidyahBerasPerHari),
            'jiwa' => $item['jiwa'] ?? $data['jiwa'] ?? null,
            'hari' => $item['hari'] ?? $data['hari'] ?? null,
            'is_khusus' => $this->isSpecialValue($itemForComputation, $defaults),
            'default_fitrah_cash_per_jiwa_used' => $defaults->fitrahCashPerJiwa > 0 ? $defaults->fitrahCashPerJiwa : null,
            'default_fidyah_per_hari_used' => $defaults->fidyahPerHari > 0 ? $defaults->fidyahPerHari : null,
            'petugas_id' => $petugasId,
            'waktu_terima' => $waktuTerima,
            'shift' => $data['shift'] ?? ZakatTransaction::SHIFT_PAGI,
            'keterangan' => $data['keterangan'] ?? null,
            'is_transfer' => ($metode === ZakatTransaction::METHOD_UANG) ? ($item['is_transfer'] ?? false) : false,
            'status' => ZakatTransaction::STATUS_VALID,
            'pembayar_nama' => $payerData['muzakki_name'],
            'pembayar_alamat' => $payerData['muzakki_address'],
            'pembayar_phone' => $payerData['muzakki_phone'],
        ];
    }

    private function isSpecialValue(array $data, AnnualZakatDefaults $defaults): bool
    {
        $category = $data['category'];

        if ($data['metode'] === ZakatTransaction::METHOD_BERAS) {
            $value = $data['jumlah_beras_kg'] ?? null;
            if ($value === null || $value === '') {
                return false;
            }

            if ($category === ZakatTransaction::CATEGORY_FITRAH && $defaults->fitrahBerasPerJiwa > 0) {
                return abs((float) $value - (((int) ($data['jiwa'] ?? 1)) * $defaults->fitrahBerasPerJiwa)) > 0.001;
            }

            if ($category === ZakatTransaction::CATEGORY_FIDYAH && $defaults->fidyahBerasPerHari > 0) {
                return abs((float) $value - (((int) ($data['hari'] ?? 0)) * $defaults->fidyahBerasPerHari)) > 0.001;
            }

            return false;
        }

        $value = $data['nominal_uang'] ?? null;
        if ($value === null || $value === '') {
            return false;
        }

        if ($category === ZakatTransaction::CATEGORY_FITRAH && $defaults->fitrahCashPerJiwa > 0) {
            return (int) $value !== (((int) ($data['jiwa'] ?? 1)) * $defaults->fitrahCashPerJiwa);
        }

        if ($category === ZakatTransaction::CATEGORY_FIDYAH && $defaults->fidyahPerHari > 0) {
            return (int) $value !== (((int) ($data['hari'] ?? 0)) * $defaults->fidyahPerHari);
        }

        return false;
    }
}

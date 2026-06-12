<?php

namespace App\Services\Transactions;

use App\Models\Muzakki;

class MuzakkiResolver
{
    /** @return array{muzakki_name:string,muzakki_phone:string,muzakki_address:string} */
    public function payerData(array $data): array
    {
        return [
            'muzakki_name' => $data['pembayar_nama'],
            'muzakki_phone' => $data['pembayar_phone'] ?? '',
            'muzakki_address' => $data['pembayar_alamat'],
        ];
    }

    public function resolvePayer(array $data): Muzakki
    {
        $payerData = $this->payerData($data);
        return app(\App\Services\Muzakki\MuzakkiProfileBuilderService::class)->resolveProfile(
            $payerData['muzakki_name'],
            $payerData['muzakki_phone'],
            $payerData['muzakki_address']
        );
    }

    public function resolveItem(array $item, array $payerData): Muzakki
    {
        $itemData = $this->itemData($item, $payerData);
        return app(\App\Services\Muzakki\MuzakkiProfileBuilderService::class)->resolveProfile(
            $itemData['muzakki_name'],
            $itemData['muzakki_phone'],
            $itemData['muzakki_address']
        );
    }

    /** @return array{muzakki_name:string,muzakki_phone:string,muzakki_address:string} */
    private function itemData(array $item, array $payerData): array
    {
        return [
            'muzakki_name' => $item['muzakki_name'] ?? $payerData['muzakki_name'],
            'muzakki_phone' => $item['muzakki_phone'] ?? $payerData['muzakki_phone'] ?? '',
            'muzakki_address' => $item['muzakki_address'] ?? $payerData['muzakki_address'] ?? '',
        ];
    }
}

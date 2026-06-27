<?php

namespace Tests\Unit;

use App\Services\AutocompleteService;
use App\Models\ZakatTransaction;
use Tests\TestCase;

class AutocompleteServiceTest extends TestCase
{
    public function test_get_autocomplete_data_returns_unique_pembayar_names(): void
    {
        ZakatTransaction::create(['pembayar_name' => 'Ahmad Hidayat', 'nominal' => 100000, 'jenis_zakat' => 'mal']);
        ZakatTransaction::create(['pembayar_name' => 'Ahmad Hidayat', 'nominal' => 100000, 'jenis_zakat' => 'mal']);
        ZakatTransaction::create(['pembayar_name' => 'Budi Santoso', 'nominal' => 100000, 'jenis_zakat' => 'mal']);

        $data = AutocompleteService::getAutocompleteData(['pembayar_name']);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('pembayar_name', $data);
        $this->assertCount(2, $data['pembayar_name']);
        $this->assertContains('Ahmad Hidayat', $data['pembayar_name']);
        $this->assertContains('Budi Santoso', $data['pembayar_name']);
    }

    public function test_get_autocomplete_data_with_no_types_returns_all(): void
    {
        ZakatTransaction::create(['pembayar_name' => 'Ahmad', 'nominal' => 100000, 'jenis_zakat' => 'mal']);
        ZakatTransaction::create(['penerima_name' => 'Yayasan X', 'nominal' => 100000, 'jenis_zakat' => 'mal']);

        $data = AutocompleteService::getAutocompleteData([]);

        $this->assertArrayHasKey('pembayar_name', $data);
        $this->assertArrayHasKey('penerima_name', $data);
        $this->assertArrayHasKey('category', $data);
    }
}

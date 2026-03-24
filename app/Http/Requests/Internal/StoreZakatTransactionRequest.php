<?php

namespace App\Http\Requests\Internal;

use App\Models\AnnualSetting;
use App\Models\ZakatTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreZakatTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Global fields
            'pembayar_nama' => ['required', 'string', 'max:255'],
            'pembayar_phone' => ['nullable', 'string', 'max:50'],
            'pembayar_alamat' => ['required', 'string'],
            'tahun_zakat' => ['required', 'integer', 'min:2000', 'max:2100'],
            'waktu_terima' => ['nullable', 'date'],
            'shift' => ['required', 'string', Rule::in(ZakatTransaction::SHIFTS)],
            'keterangan' => ['nullable', 'string'],

            // Legacy individual fields (if not batch)
            'muzakki_name' => ['required_without:items', 'string', 'max:255', 'nullable'],
            'muzakki_address' => ['nullable', 'string'],
            'muzakki_phone' => ['nullable', 'string', 'max:50'],
            'category' => ['required_without:items', 'string', Rule::in(ZakatTransaction::CATEGORIES), 'nullable'],
            'metode' => ['required_without:items', 'string', Rule::in(ZakatTransaction::METHODS), 'nullable'],
            'jiwa' => ['nullable', 'integer', 'min:1'],
            'hari' => ['nullable', 'integer', 'min:1'],
            'nominal_uang' => ['nullable', 'numeric', 'min:0'],
            'jumlah_beras_kg' => ['nullable', 'numeric', 'min:0'],

            // Batch items array (capped at 30 to prevent memory exhaustion)
            'items' => ['required_without:muzakki_name', 'array', 'min:1', 'max:30'],
            'items.*.muzakki_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.category' => [
                Rule::requiredIf(fn() => $this->filled('items') && !$this->filled('category')),
                'nullable', 'string', Rule::in(ZakatTransaction::CATEGORIES)
            ],
            'items.*.metode' => [
                Rule::requiredIf(fn() => $this->filled('items') && !$this->filled('metode')),
                'nullable', 'string', Rule::in(ZakatTransaction::METHODS)
            ],
            'items.*.jiwa' => ['nullable', 'integer', 'min:1'],
            'items.*.hari' => ['nullable', 'integer', 'min:1'],
            'items.*.nominal_uang' => ['nullable', 'numeric', 'min:0'],
            'items.*.jumlah_beras_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.is_transfer' => ['nullable', 'boolean'],
            'items.*.id' => ['nullable', 'integer', 'exists:zakat_transactions,id'],
        ];
    }


    public function messages(): array
    {
        return [
            'muzakki_name.required' => 'Nama muzakki wajib diisi.',
            'category.required' => 'Kategori zakat wajib dipilih.',
            'metode.required' => 'Metode pembayaran wajib dipilih.',
            'shift.required' => 'Shift wajib dipilih.',
        ];
    }
}

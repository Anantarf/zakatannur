<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

class StoreInputTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pembayar_nama' => ['required', 'string', 'max:255'],
            'pembayar_alamat' => ['required', 'string', 'max:255'],
            'pembayar_phone' => ['nullable', 'string', 'max:20'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['in:fitrah,mal,fidyah,infaq'],
            // Fitrah
            'fitrah_metode' => ['nullable', 'in:uang,beras'],
            'fitrah_per_jiwa' => ['nullable', 'integer', 'min:1'],
            'fitrah_khusus' => ['nullable', 'boolean'],
            // Mal
            'mal_metode' => ['nullable', 'in:uang,cek'],
            'mal_nominal' => ['nullable', 'integer', 'min:0'],
            // Fidyah
            'fidyah_metode' => ['nullable', 'in:beras,uang'],
            'fidyah_hari' => ['nullable', 'integer', 'min:1'],
            // Infaq
            'infaq_nominal' => ['nullable', 'integer', 'min:0'],
            // Muzakki
            'pembayar_is_muzakki' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $categories = $this->input('categories', []);
            if (count($categories) !== count(array_unique($categories))) {
                $v->errors()->add('categories', 'Kategori tidak boleh duplikat.');
            }
            // Example mutually exclusive: fitrah & mal (customize as needed)
            if (in_array('fitrah', $categories) && in_array('mal', $categories)) {
                $v->errors()->add('categories', 'Fitrah dan Mal tidak boleh dipilih bersamaan.');
            }
        });
    }
}

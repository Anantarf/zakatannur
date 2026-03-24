<?php

namespace App\Support;

use App\Models\ZakatTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class RekapFilters
{
    /**
     * @return array{year:?int,metode:?string}
     */
    public static function fromRequest(Request $request): array
    {
        $validated = Validator::make($request->query(), [
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'metode' => ['nullable', 'string', Rule::in(ZakatTransaction::METHODS)],
        ])->validate();

        return [
            'year' => isset($validated['year']) ? (int) $validated['year'] : null,
            'metode' => $validated['metode'] ?? null,
        ];
    }
}

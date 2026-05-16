<?php

namespace App\Services;

use App\Models\ZakatTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class DashboardRekapFilters
{
    public ?int $year;
    public ?string $metode;

    public function __construct(?int $year, ?string $metode)
    {
        $this->year = $year;
        $this->metode = $metode;
    }

    public static function fromRequest(Request $request): self
    {
        $validated = Validator::make($request->query(), [
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'metode' => ['nullable', 'string', Rule::in(ZakatTransaction::METHODS)],
        ])->validate();

        return new self(
            isset($validated['year']) ? (int) $validated['year'] : null,
            $validated['metode'] ?? null
        );
    }
}

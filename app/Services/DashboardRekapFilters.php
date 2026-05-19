<?php

namespace App\Services;

use App\Models\ZakatTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class DashboardRekapFilters
{
    public ?int $year;
    public ?int $periodId;
    public ?string $metode;

    public function __construct(?int $year, ?int $periodId, ?string $metode)
    {
        $this->year = $year;
        $this->periodId = $periodId;
        $this->metode = $metode;
    }

    public static function fromRequest(Request $request): self
    {
        $validated = Validator::make($request->query(), [
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'period_id' => ['nullable', 'integer', 'exists:zakat_periods,id'],
            'metode' => ['nullable', 'string', Rule::in(ZakatTransaction::METHODS)],
        ])->validate();

        return new self(
            isset($validated['year']) ? (int) $validated['year'] : null,
            isset($validated['period_id']) ? (int) $validated['period_id'] : null,
            $validated['metode'] ?? null
        );
    }
}

<?php

namespace App\Services\Transactions;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use App\Support\ViewOptions;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransactionHistoryService
{
    public function __construct(
        private GroupedTransactionQueryService $groupedQueryService,
    ) {
    }

    /**
     * @return TransactionHistoryFilters
     */
    public function parseFilters(Request $request): TransactionHistoryFilters
    {
        $activeYear = AppSetting::getInt(AppSetting::KEY_ACTIVE_YEAR, (int) now()->year);

        $validated = Validator::make($request->query(), [
            'q' => ['nullable', 'string', 'max:' . (int) config('zakat.validation.search_query_max', 100)],
            'year' => ['nullable', 'integer', 'min:' . (int) config('zakat.year_bounds.min', 2000), 'max:' . (int) config('zakat.year_bounds.max', 2100)],
            'category' => ['nullable', 'string', Rule::in(ZakatTransaction::CATEGORIES)],
            'metode' => ['nullable', 'string', Rule::in(ZakatTransaction::METHODS)],
            'status' => ['nullable', 'string', Rule::in(ZakatTransaction::STATUSES)],
            'petugas_id' => ['nullable', 'integer', 'exists:users,id'],
        ])->validate();

        $q = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $year = array_key_exists('year', $validated)
            ? ($validated['year'] !== null ? (int) $validated['year'] : null)
            : $activeYear;

        return TransactionHistoryFilters::fromArray([
            'q' => $q,
            'year' => $year,
            'category' => $validated['category'] ?? null,
            'metode' => $validated['metode'] ?? null,
            'status' => $validated['status'] ?? null,
            'petugasId' => isset($validated['petugas_id']) ? (int) $validated['petugas_id'] : null,
            'activeYear' => $activeYear,
        ]);
    }

    public function paginatedHistory(TransactionHistoryFilters $filters, array $queryParams): LengthAwarePaginator
    {
        return $this->groupedQueryService->make()
            ->with(['petugas'])
            ->filter($filters->toArray())
            ->groupBy('no_transaksi')
            ->orderByRaw('COALESCE(MAX(waktu_terima), MAX(created_at)) DESC')
            ->orderByDesc('no_transaksi')
            ->paginate(20)
            ->appends($queryParams);
    }

    public function paginatedTrash(string $query, array $queryParams): LengthAwarePaginator
    {
        $transactions = $this->groupedQueryService->make(true)
            ->with(['petugas'])
            ->when($query !== '', function (Builder $builder) use ($query) {
                $like = '%' . str_replace('%', '\\%', $query) . '%';
                $builder->where(function (Builder $subQuery) use ($like) {
                    $subQuery->where('no_transaksi', 'like', $like)
                        ->orWhere('pembayar_nama', 'like', $like);
                });
            })
            ->groupBy('no_transaksi')
            ->orderByDesc('deleted_at')
            ->paginate(20)
            ->appends($queryParams);

        $purgeDays = (int) config('zakat.retention.purge_days', 30);

        $transactions->getCollection()->transform(function ($transaction) use ($purgeDays) {
            $deletedAt = $transaction->deleted_at
                ? Carbon::parse($transaction->deleted_at)->setTimezone(config('zakat.timezone'))
                : null;

            $transaction->days_left = $deletedAt
                ? max(0, $purgeDays - (int) $deletedAt->startOfDay()->diffInDays(now(config('zakat.timezone'))->startOfDay()))
                : null;
            $transaction->deleted_at_formatted = $deletedAt ? $deletedAt->format('d/m/Y H:i') : '-';

            return $transaction;
        });

        return $transactions;
    }

    public function indexViewData(TransactionHistoryFilters $filters): array
    {
        $availableDates = ZakatTransaction::valid()
            ->selectRaw('DISTINCT DATE(COALESCE(waktu_terima, created_at)) as date')
            ->orderByDesc('date')
            ->pluck('date')
            ->mapWithKeys(function ($date) {
                return [$date => Carbon::parse($date)->locale('id')->translatedFormat('d F Y')];
            });

        return array_merge($filters->toArray(), [
            'years' => ViewOptions::years($filters->activeYear),
            'categories' => ZakatTransaction::CATEGORIES,
            'methods' => ZakatTransaction::METHODS,
            'statuses' => ZakatTransaction::STATUSES,
            'petugasOptions' => ViewOptions::petugasOptions(),
            'availableDates' => $availableDates,
            'availableYears' => ZakatTransaction::valid()
                ->distinct()
                ->orderByDesc('tahun_zakat')
                ->pluck('tahun_zakat'),
        ]);
    }
}

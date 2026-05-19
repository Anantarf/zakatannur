<?php

namespace App\Services\Transactions;

use App\Models\ZakatTransaction;
use App\Support\SqlDialect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GroupedTransactionQueryService
{
    public function make(bool $onlyTrashed = false): Builder
    {
        $query = $onlyTrashed ? ZakatTransaction::onlyTrashed() : ZakatTransaction::query();

        return $query->select([
            'no_transaksi',
            DB::raw('MAX(id) as id'),
            DB::raw('MAX(waktu_terima) as waktu_terima'),
            DB::raw('MAX(created_at) as created_at'),
            DB::raw('SUM(nominal_uang) as total_uang'),
            DB::raw('SUM(jumlah_beras_kg) as total_beras'),
            DB::raw('MAX(pembayar_nama) as pembayar_nama'),
            DB::raw('MAX(petugas_id) as petugas_id'),
            DB::raw('MAX(zakat_period_id) as zakat_period_id'),
            DB::raw('MAX(shift) as shift'),
            DB::raw(SqlDialect::stringAggregateDistinct('category', 'categories_list')),
            DB::raw(SqlDialect::stringAggregateDistinct('metode', 'methods_list')),
            DB::raw('COUNT(DISTINCT muzakki_id) as muzakki_total'),
            DB::raw(SqlDialect::moneyTransferAggregate()),
            ...($onlyTrashed ? [DB::raw('MAX(deleted_at) as deleted_at')] : []),
        ]);
    }

    public function latestValid(?int $year = null, ?string $metode = null, int $limit = 10, ?int $periodId = null)
    {
        return $this->make()
            ->with(['petugas'])
            ->valid()
            ->forPeriodOrYear($periodId, $year)
            ->when($metode !== null && $metode !== '', fn ($query) => $query->where('metode', $metode))
            ->groupBy('no_transaksi')
            ->orderByRaw(SqlDialect::maxEffectiveTimestampOrder())
            ->orderByDesc('no_transaksi')
            ->limit($limit)
            ->get();
    }
}

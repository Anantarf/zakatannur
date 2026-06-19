<?php

namespace App\Observers;

use App\Models\AppSetting;
use App\Models\ZakatTransaction;
use Illuminate\Support\Facades\Cache;

class ZakatTransactionCacheObserver
{
    private function clearPublicCache(ZakatTransaction $model): void
    {
        $year = $model->tahun_zakat;
        Cache::forget(AppSetting::cacheKeyForPublicSummary($year));
        Cache::forget(AppSetting::cacheKeyForPublicHomeStats($year));
    }

    public function created(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
    }

    public function updated(ZakatTransaction $zakatTransaction): void
    {
        if ($zakatTransaction->wasChanged()) {
            $this->clearPublicCache($zakatTransaction);
        }
    }

    public function deleted(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
    }

    public function restored(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
    }

    public function forceDeleted(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
    }
}

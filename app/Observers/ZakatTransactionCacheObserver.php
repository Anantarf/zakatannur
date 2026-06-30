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

    private function clearAnomalyAvgCache(ZakatTransaction $model): void
    {
        Cache::forget("anomaly:avg_nominal_uang:{$model->category}:{$model->metode}");

        $originalCategory = $model->getOriginal('category');
        $originalMetode = $model->getOriginal('metode');

        if ($originalCategory && $originalMetode) {
            Cache::forget("anomaly:avg_nominal_uang:{$originalCategory}:{$originalMetode}");
        }
    }

    public function created(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
        $this->clearAnomalyAvgCache($zakatTransaction);
    }

    public function updated(ZakatTransaction $zakatTransaction): void
    {
        if ($zakatTransaction->wasChanged()) {
            $this->clearPublicCache($zakatTransaction);
            if ($zakatTransaction->wasChanged(['nominal_uang', 'category', 'metode', 'status'])) {
                $this->clearAnomalyAvgCache($zakatTransaction);
            }
        }
    }

    public function deleted(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
        $this->clearAnomalyAvgCache($zakatTransaction);
    }

    public function restored(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
        $this->clearAnomalyAvgCache($zakatTransaction);
    }

    public function forceDeleted(ZakatTransaction $zakatTransaction): void
    {
        $this->clearPublicCache($zakatTransaction);
        $this->clearAnomalyAvgCache($zakatTransaction);
    }
}

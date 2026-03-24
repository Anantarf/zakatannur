<?php

namespace App\Observers;

use App\Models\ZakatTransaction;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class ZakatTransactionObserver
{
    private function clearPublicCache(ZakatTransaction $model)
    {
        $year = $model->tahun_zakat;
        \Illuminate\Support\Facades\Cache::forget('public_summary_year_' . $year);
        \Illuminate\Support\Facades\Cache::forget('public_home_stats_' . $year);
    }

    private function log(ZakatTransaction $model, string $action, array $metadata = [])
    {
        $this->clearPublicCache($model);

        AuditLog::create([
            'actor_user_id' => auth()->id() ?? $model->petugas_id,
            'action'        => $action,
            'subject_type'  => ZakatTransaction::class,
            'subject_id'    => $model->id,
            'metadata'      => $metadata,
            'ip'            => Request::ip(),
            'user_agent'    => Request::userAgent(),
        ]);
    }

    public function created(ZakatTransaction $zakatTransaction)
    {
        $this->log($zakatTransaction, 'created', [
            'new' => $zakatTransaction->getAttributes()
        ]);
    }

    public function updated(ZakatTransaction $zakatTransaction)
    {
        // wasChanged is appropriate for the 'updated' event as it fires AFTER save
        if ($zakatTransaction->wasChanged()) {
            $this->log($zakatTransaction, 'updated', [
                'old' => array_intersect_key($zakatTransaction->getOriginal(), $zakatTransaction->getChanges()),
                'new' => $zakatTransaction->getChanges(),
            ]);
        }
    }

    public function deleted(ZakatTransaction $zakatTransaction)
    {
        $this->log($zakatTransaction, 'deleted', [
            'old' => $zakatTransaction->getAttributes()
        ]);
    }

    public function restored(ZakatTransaction $zakatTransaction)
    {
        $this->log($zakatTransaction, 'restored');
    }

    public function forceDeleted(ZakatTransaction $zakatTransaction)
    {
        $this->log($zakatTransaction, 'force_deleted');
    }
}


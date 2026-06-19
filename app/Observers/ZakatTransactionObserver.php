<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\ZakatTransaction;
use Illuminate\Support\Facades\Request;

class ZakatTransactionObserver
{
    private function log(ZakatTransaction $model, string $action, array $metadata = []): void
    {
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

    public function created(ZakatTransaction $zakatTransaction): void
    {
        $this->log($zakatTransaction, 'created', [
            'new' => $zakatTransaction->getAttributes(),
        ]);
    }

    public function updated(ZakatTransaction $zakatTransaction): void
    {
        if ($zakatTransaction->wasChanged()) {
            $this->log($zakatTransaction, 'updated', [
                'old' => array_intersect_key($zakatTransaction->getOriginal(), $zakatTransaction->getChanges()),
                'new' => $zakatTransaction->getChanges(),
            ]);
        }
    }

    public function deleted(ZakatTransaction $zakatTransaction): void
    {
        $this->log($zakatTransaction, 'deleted', [
            'old' => $zakatTransaction->getAttributes(),
        ]);
    }

    public function restored(ZakatTransaction $zakatTransaction): void
    {
        $this->log($zakatTransaction, 'restored');
    }

    public function forceDeleted(ZakatTransaction $zakatTransaction): void
    {
        $this->log($zakatTransaction, 'force_deleted');
    }
}

<?php

namespace App\Observers;

use App\Models\Muzakki;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class MuzakkiObserver
{
    private function log(Muzakki $model, string $action, array $metadata = [])
    {
        AuditLog::create([
            'actor_user_id' => auth()->id(),
            'action'        => $action,
            'subject_type'  => Muzakki::class,
            'subject_id'    => $model->id,
            'metadata'      => $metadata,
            'ip'            => Request::ip(),
            'user_agent'    => Request::userAgent(),
        ]);
    }

    // Muzakki creation and updates are no longer logged per user request to keep audit logs clean.


    public function deleted(Muzakki $muzakki)
    {
        // Muzakki activity is no longer logged to keep audit logs focused on transactions.
    }

    public function restored(Muzakki $muzakki)
    {
        // Muzakki activity is no longer logged to keep audit logs focused on transactions.
    }
}

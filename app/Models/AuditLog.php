<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'actor_user_id' => 'integer',
    ];

    public function actorUser()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match (true) {
            $this->action === 'login' => 'Login',
            $this->action === 'logout' => 'Logout',
            $this->action === 'settings.period.updated' => 'Pengaturan Periode Diperbarui',
            $this->action === 'transaction.risk_review_status_updated' => 'Status Review Risiko Diubah',
            $this->action === 'transaction.delete' => 'Dipindah ke Sampah',
            $this->action === 'Deleted.Permanently.Transaction' => 'Dihapus Permanen',
            $this->action === 'Restored.Transaction' => 'Transaksi Dipulihkan',
            $this->action === 'system.bulk_transaction_cleanup' => 'Pembersihan Massal Transaksi',
            $this->action === 'template.deleted' => 'Template Dihapus',
            $this->action === 'Created.Transaction' || Str::contains($this->action, 'created') => 'Dibuat',
            $this->action === 'Updated.Transaction' || Str::contains($this->action, 'updated') => 'Diperbarui',
            Str::contains($this->action, 'restored') => 'Dipulihkan',
            Str::contains($this->action, 'deleted') => 'Dihapus',
            default => Str::headline(str_replace(['.', '_'], ' ', $this->action)),
        };
    }
}

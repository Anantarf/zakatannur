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

    public function getActionColorClassAttribute(): string
    {
        return match (true) {
            $this->action === 'Created.Transaction' || Str::contains($this->action, 'created') => 'bg-emerald-100 text-emerald-700',
            $this->action === 'Updated.Transaction' || Str::contains($this->action, 'updated') => 'bg-amber-100 text-amber-700',
            $this->action === 'transaction.delete' => 'bg-pink-100 text-pink-700',
            $this->action === 'Deleted.Permanently.Transaction' => 'bg-red-900 text-white',
            $this->action === 'Restored.Transaction' || Str::contains($this->action, 'restored') => 'bg-indigo-100 text-indigo-700',
            $this->action === 'login' => 'bg-blue-100 text-blue-700',
            $this->action === 'logout' => 'bg-slate-200 text-slate-700',
            Str::contains($this->action, 'deleted') => 'bg-red-100 text-red-700',
            default => 'bg-slate-100 text-slate-700',
        };
    }
}
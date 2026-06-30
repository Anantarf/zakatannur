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

    protected static function booted()
    {
        static::created(function ($log) {
            // Only purge when over limit — count() is cheap vs skip(4999)->first()
            if (static::count() > 5000) {
                $thresholdRecord = static::orderBy('id', 'desc')->skip(4999)->first(['id']);
                if ($thresholdRecord) {
                    static::where('id', '<', $thresholdRecord->id)->delete();
                }
            }
        });
    }

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

    public function getRiskFlagsAttribute(): array
    {
        $flags = [];

        if (!is_array($this->metadata)) {
            return $flags;
        }

        // Perubahan nominal > 50%
        if (in_array($this->action, ['Updated.Transaction']) && isset($this->metadata['totals'])) {
            $old = (int) ($this->metadata['totals']['old']['uang'] ?? 0);
            $new = (int) ($this->metadata['totals']['new']['uang'] ?? 0);
            if ($old > 0 && abs($new - $old) / $old > (float) config('zakat.thresholds.significant_change_percent', 0.5)) {
                $flags[] = 'perubahan_nominal_besar';
            }
        }

        // Penghapusan > 3 item
        if (in_array($this->action, ['Updated.Transaction']) && isset($this->metadata['summary']['removed'])) {
            if ($this->metadata['summary']['removed'] > 3) {
                $flags[] = 'penghapusan_multipel';
            }
        }

        // Perubahan pembayar setelah dibuat
        if ($this->action === 'Updated.Transaction' && isset($this->metadata['old']['pembayar_nama']) && isset($this->metadata['new']['pembayar_nama'])) {
            if ($this->metadata['old']['pembayar_nama'] !== $this->metadata['new']['pembayar_nama']) {
                $flags[] = 'pembayar_berubah';
            }
        }

        return $flags;
    }

    public function hasRiskFlags(): bool
    {
        return count($this->risk_flags) > 0;
    }
}
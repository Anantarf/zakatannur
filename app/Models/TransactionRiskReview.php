<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRiskReview extends Model
{
    use HasFactory;

    public const LEVEL_NORMAL = 'normal';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_SUSPICIOUS = 'suspicious';

    public const REVIEW_BELUM_DITINJAU = 'belum_ditinjau';
    public const REVIEW_AMAN = 'aman';
    public const REVIEW_PERLU_TINDAK_LANJUT = 'perlu_tindak_lanjut';

    public const LEVELS = [
        self::LEVEL_NORMAL,
        self::LEVEL_WARNING,
        self::LEVEL_SUSPICIOUS,
    ];

    public const REVIEW_STATUSES = [
        self::REVIEW_BELUM_DITINJAU,
        self::REVIEW_AMAN,
        self::REVIEW_PERLU_TINDAK_LANJUT,
    ];

    protected $fillable = [
        'zakat_transaction_id',
        'group_no_transaksi',
        'risk_level',
        'risk_score',
        'risk_flags',
        'reasons',
        'duplicate_candidates',
        'detector_version',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'checked_at',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'risk_flags' => 'array',
        'reasons' => 'array',
        'duplicate_candidates' => 'array',
        'reviewed_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(ZakatTransaction::class, 'zakat_transaction_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRiskReview extends Model
{
    use HasFactory;

    public const LEVEL_SAFE = 'safe';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_SUSPICIOUS = 'suspicious';

    public const REVIEW_BELUM_DITINJAU = 'belum_ditinjau';
    public const REVIEW_AMAN = 'aman';
    public const REVIEW_PERLU_TINDAK_LANJUT = 'perlu_tindak_lanjut';

    public const FLAG_RESTORED_AFTER_DELETE = 'restored_after_delete';
    public const FLAG_UPDATED_AFTER_RECEIPT_PRINTED = 'updated_after_receipt_printed';
    public const FLAG_SIGNIFICANT_NOMINAL_CHANGE = 'significant_nominal_change';
    public const FLAG_STATISTICAL_OUTLIER = 'statistical_outlier';
    public const FLAG_EXACT_DUPLICATE = 'exact_duplicate';
    public const FLAG_TRANSFER_DUPLICATE_CANDIDATE = 'transfer_duplicate_candidate';
    public const FLAG_PAYER_MATCH_SAME_BENEFICIARY = 'payer_match_same_beneficiary';
    public const FLAG_PAYER_MATCH_DIFFERENT_BENEFICIARY = 'payer_match_different_beneficiary';

    public const ANOMALY_FLAG_RESTORE_SCORE = 25;
    public const SCORE_UPDATED_AFTER_RECEIPT = 30;
    public const SCORE_SIGNIFICANT_NOMINAL_CHANGE = 35;

    public const LEVELS = [
        self::LEVEL_SAFE,
        self::LEVEL_WARNING,
        self::LEVEL_SUSPICIOUS,
    ];

    public const LEVEL_LABELS = [
        self::LEVEL_SAFE => 'Aman',
        self::LEVEL_WARNING => 'Warning',
        self::LEVEL_SUSPICIOUS => 'Suspicious',
    ];

    public const REVIEW_STATUSES = [
        self::REVIEW_BELUM_DITINJAU,
        self::REVIEW_AMAN,
        self::REVIEW_PERLU_TINDAK_LANJUT,
    ];

    public const REVIEW_STATUS_LABELS = [
        self::REVIEW_BELUM_DITINJAU => 'Belum Ditinjau',
        self::REVIEW_AMAN => 'Aman',
        self::REVIEW_PERLU_TINDAK_LANJUT => 'Tindak Lanjut',
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
        'review_note',
        'reviewed_by',
        'reviewed_at',
        'checked_at',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'risk_flags' => 'array',
        'reasons' => 'array',
        'duplicate_candidates' => 'array',
        'reviewed_by' => 'integer',
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

    public static function levelLabel(?string $level): string
    {
        return self::LEVEL_LABELS[$level] ?? 'Belum Analisis';
    }

    public static function flagLabel(?string $flag): string
    {
        if ($flag === null || $flag === '') {
            return '-';
        }

        $meta = (array) \Illuminate\Support\Facades\Lang::get('anomaly.flags.' . $flag, []);
        if (isset($meta['label']) && $meta['label'] !== '') {
            return (string) $meta['label'];
        }

        return ucfirst(str_replace('_', ' ', $flag));
    }

    public static function reviewStatusLabel(?string $status): string
    {
        return self::REVIEW_STATUS_LABELS[$status] ?? 'Belum Ditinjau';
    }
}

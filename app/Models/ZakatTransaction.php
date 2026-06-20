<?php

namespace App\Models;

use App\Support\SqlDialect;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ZakatTransaction extends Model
{
    use HasFactory;

    use SoftDeletes;

    public const CATEGORY_FITRAH = 'fitrah';
    public const CATEGORY_FIDYAH = 'fidyah';
    public const CATEGORY_MAL = 'mal';
    public const CATEGORY_INFAK = 'infaq';

    public const METHOD_UANG = 'uang';
    public const METHOD_BERAS = 'beras';
    public const METHOD_CEK = 'cek';

    public const SHIFT_PAGI  = 'pagi';
    public const SHIFT_SIANG = 'siang';
    public const SHIFT_MALAM = 'malam';

    public const SHIFTS = [
        self::SHIFT_PAGI,
        self::SHIFT_SIANG,
        self::SHIFT_MALAM,
    ];

    public const SHIFT_LABELS = [
        self::SHIFT_PAGI  => 'Pagi',
        self::SHIFT_SIANG => 'Siang',
        self::SHIFT_MALAM => 'Malam',
    ];

    public const STATUS_VALID = 'valid';
    public const STATUS_VOID = 'void';

    public const CATEGORIES = [
        self::CATEGORY_FITRAH,
        self::CATEGORY_FIDYAH,
        self::CATEGORY_MAL,
        self::CATEGORY_INFAK,
    ];

    public const METHODS = [
        self::METHOD_UANG,
        self::METHOD_BERAS,
        self::METHOD_CEK,
    ];

    public const STATUSES = [
        self::STATUS_VALID,
        self::STATUS_VOID,
    ];

    public const CATEGORY_LABELS = [
        self::CATEGORY_FITRAH => 'Zakat Fitrah',
        self::CATEGORY_FIDYAH => 'Fidyah',
        self::CATEGORY_MAL    => 'Zakat Mal',
        self::CATEGORY_INFAK  => 'Infaq Shodaqoh',
    ];

    public const METHOD_LABELS = [
        self::METHOD_UANG  => 'Uang',
        self::METHOD_BERAS => 'Beras',
        self::METHOD_CEK   => 'Cek',
    ];

    public static function isValidCategory(?string $value): bool
    {
        return self::isValidValue($value, self::CATEGORIES);
    }

    public static function isValidMethod(?string $value): bool
    {
        return self::isValidValue($value, self::METHODS);
    }

    public static function isValidStatus(?string $value): bool
    {
        return self::isValidValue($value, self::STATUSES);
    }

    private static function isValidValue(?string $value, array $allowed): bool
    {
        return $value !== null && in_array($value, $allowed, true);
    }

    public static function getShiftLabel(?string $shift): string
    {
        return self::SHIFT_LABELS[$shift] ?? '-';
    }

    protected $fillable = [
            // --- User-submitted form fields ---
            'no_transaksi',
            'shift',
            'muzakki_id',
            'category',
            'tahun_zakat',
            'zakat_period_id',
            'hijri_year',
            'hijri_month',
            'metode',
            'nominal_uang',
            'jumlah_beras_kg',
            'jiwa',
            'hari',
            'is_transfer',
            'keterangan',
            'waktu_terima',
            'pembayar_nama',
            'pembayar_alamat',
            'pembayar_phone',

            // --- Server-assigned only (never from user form input) ---
            'is_khusus',
            'default_fitrah_cash_per_jiwa_used',
            'default_fidyah_per_hari_used',
            'petugas_id',    // Always set from authenticated session in ZakatService

            // --- Lifecycle fields: set by controllers, never by form input ---
            'status',        // Always hardcoded to STATUS_VALID on create/update
            'void_reason',   // Set only by TransactionHistoryController::void()
            'voided_at',
            'voided_by',
            'deleted_by',    // Set only by TransactionHistoryController::destroy()
            'deleted_reason',
            'restored_at',   // Set only by TransactionHistoryController::restore()
            'restored_by',
            'receipt_printed_at',
            'receipt_printed_by',
        ];

    protected $casts = [
        'tahun_zakat' => 'integer',
        'zakat_period_id' => 'integer',
        'hijri_year' => 'integer',
        'hijri_month' => 'integer',
        'is_transfer' => 'boolean',
        'nominal_uang' => 'integer',
        'jumlah_beras_kg' => 'decimal:2',
        'jiwa' => 'integer',
        'hari' => 'integer',
        'is_khusus' => 'boolean',
        'default_fitrah_cash_per_jiwa_used' => 'integer',
        'default_fidyah_per_hari_used' => 'integer',
        'voided_at' => 'datetime',
        'waktu_terima' => 'datetime',
        'deleted_at' => 'datetime',
        'deleted_by' => 'integer',
        'restored_at' => 'datetime',
        'restored_by' => 'integer',
        'receipt_printed_at' => 'datetime',
        'receipt_printed_by' => 'integer',
    ];

    public function muzakki(): BelongsTo
    {
        return $this->belongsTo(Muzakki::class, 'muzakki_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function zakatPeriod(): BelongsTo
    {
        return $this->belongsTo(ZakatPeriod::class, 'zakat_period_id');
    }

    public function riskReview(): HasOne
    {
        return $this->hasOne(TransactionRiskReview::class, 'zakat_transaction_id');
    }

    /*
     |--------------------------------------------------------------------------
     | Attribute Accessors
     |--------------------------------------------------------------------------
     */

    protected function categoryLabel(): Attribute
    {
        return Attribute::get(fn() => self::CATEGORY_LABELS[$this->category] ?? strtoupper($this->category));
    }

    protected function metodeLabel(): Attribute
    {
        return Attribute::get(fn() => self::METHOD_LABELS[$this->metode] ?? strtoupper($this->metode));
    }

    protected function shiftLabel(): Attribute
    {
        return Attribute::get(fn() => self::SHIFT_LABELS[$this->shift] ?? strtoupper((string) $this->shift) ?: '-');
    }

    protected function nominalUangDisplay(): Attribute
    {
        return Attribute::get(fn() => \App\Support\Format::rupiah((int)($this->nominal_uang ?? 0)));
    }

    protected function jumlahBerasKgDisplay(): Attribute
    {
        return Attribute::get(fn() => \App\Support\Format::kg((float)($this->jumlah_beras_kg ?? 0)));
    }

    protected function totalUangDisplay(): Attribute
    {
        return Attribute::get(fn() => \App\Support\Format::rupiah((int)($this->total_uang ?? $this->nominal_uang ?? 0)));
    }

    protected function totalBerasDisplay(): Attribute
    {
        return Attribute::get(fn() => \App\Support\Format::kg((float)($this->total_beras ?? $this->jumlah_beras_kg ?? 0)));
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['periodId'] ?? null, fn($q, $periodId) => $q->where('zakat_period_id', $periodId))
            ->when($filters['year'] ?? null, fn($q, $year) => $q->where('tahun_zakat', $year))
            ->when($filters['category'] ?? null, fn($q, $cat) => $q->where('category', $cat))
            ->when($filters['metode'] ?? null, fn($q, $metode) => $q->where('metode', $metode))
            ->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status))
            ->when($filters['petugasId'] ?? null, fn($q, $petugasId) => $q->where('petugas_id', $petugasId))
            ->when($filters['q'] ?? null, function ($q, $search) {
                $like = '%' . str_replace('%', '\\%', $search) . '%';
                $q->where(fn($sub) => 
                    $sub->where('no_transaksi', 'like', $like)
                        ->orWhere('pembayar_nama', 'like', $like)
                        ->orWhereHas('muzakki', fn($muzakkiQ) => 
                            $muzakkiQ->withTrashed()->where(fn($muzakkiSub) => 
                                $muzakkiSub->where('name', 'like', $like)
                                    ->orWhere('phone', 'like', $like)
                            )
                        )
                );
            });
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_VALID);
    }

    public function scopeForTransactionGroup(Builder $query, string $groupNumber): Builder
    {
        return $query->where('no_transaksi', $groupNumber);
    }

    public function scopeForPeriodOrYear(Builder $query, ?int $periodId = null, ?int $year = null): Builder
    {
        return $query
            ->when($periodId !== null, fn (Builder $q) => $q->where('zakat_period_id', $periodId))
            ->when($periodId === null && $year !== null, fn (Builder $q) => $q->where('tahun_zakat', $year));
    }

    public function scopeOrderByEffectiveTime(Builder $query): Builder
    {
        return $query
            ->orderByRaw(SqlDialect::effectiveTimestampOrder())
            ->orderByDesc('id');
    }

    public static function transactionGroupItems(
        string $groupNumber,
        bool $withTrashed = false,
        array $relations = []
    ): Collection {
        $query = $withTrashed ? static::withTrashed() : static::query();

        if ($relations !== []) {
            $query->with($relations);
        }

        return $query
            ->forTransactionGroup($groupNumber)
            ->orderBy('id', 'asc')
            ->get();
    }
}

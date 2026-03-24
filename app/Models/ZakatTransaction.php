<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        return $value !== null && in_array($value, self::CATEGORIES, true);
    }

    public static function isValidMethod(?string $value): bool
    {
        return $value !== null && in_array($value, self::METHODS, true);
    }

    public static function isValidStatus(?string $value): bool
    {
        return $value !== null && in_array($value, self::STATUSES, true);
    }

    protected $fillable = [
            'no_transaksi',
            'shift',
            'muzakki_id',
            'category',
            'tahun_zakat',
            'metode',
            'nominal_uang',
            'jumlah_beras_kg',
            'jiwa',
            'hari',
            'is_khusus',
            'default_fitrah_cash_per_jiwa_used',
            'default_fidyah_per_hari_used',
            'petugas_id',
            'keterangan',
            'is_transfer',
            'status',
            'void_reason',
            'voided_at',
            'voided_by',
            'waktu_terima',

            'deleted_by',
            'deleted_reason',
            'restored_at',
            'restored_by',

            'pembayar_nama',
            'pembayar_alamat',
            'pembayar_phone',
        ];

    protected $casts = [
        'tahun_zakat' => 'integer',
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
    ];

    public function muzakki(): BelongsTo
    {
        return $this->belongsTo(Muzakki::class, 'muzakki_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
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
        return Attribute::get(fn() => self::SHIFT_LABELS[$this->shift] ?? '-');
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

    public static function computeNominalUang(array $data, int $defaultFitrah, int $defaultFidyah): ?int
    {
        if ($data['metode'] === self::METHOD_BERAS) return null;
        if (isset($data['nominal_uang']) && $data['nominal_uang'] !== '') return (int) $data['nominal_uang'];

        if ($data['category'] === self::CATEGORY_FITRAH && $defaultFitrah > 0) return ((int) ($data['jiwa'] ?? 1)) * $defaultFitrah;
        if ($data['category'] === self::CATEGORY_FIDYAH && $defaultFidyah > 0) return ((int) ($data['hari'] ?? 0)) * $defaultFidyah;

        return null;
    }

    public static function computeJumlahBerasKg(array $data, float $defaultBerasKg, float $defaultFidyahBeras): ?float
    {
        if (isset($data['jumlah_beras_kg']) && $data['jumlah_beras_kg'] !== null && $data['jumlah_beras_kg'] !== '') return (float) $data['jumlah_beras_kg'];

        if ($data['category'] === self::CATEGORY_FITRAH && $data['metode'] === self::METHOD_BERAS) {
            return round(((int) ($data['jiwa'] ?? 1)) * $defaultBerasKg, 2);
        }
        
        if ($data['category'] === self::CATEGORY_FIDYAH && $data['metode'] === self::METHOD_BERAS) {
            return round(((int) ($data['hari'] ?? 0)) * $defaultFidyahBeras, 2);
        }

        return null;
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
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

    public function scopeOrderByEffectiveTime(Builder $query): Builder
    {
        return $query
            ->orderByRaw('COALESCE(waktu_terima, created_at) DESC')
            ->orderByDesc('id');
    }
}

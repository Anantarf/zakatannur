<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ZakatPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'gregorian_year',
        'hijri_year',
        'hijri_month',
        'sequence',
        'starts_at',
        'ends_at',
        'default_fitrah_cash_per_jiwa',
        'default_fitrah_beras_per_jiwa',
        'default_fidyah_per_hari',
        'default_fidyah_beras_per_hari',
        'chart_starts_at',
        'chart_ends_at',
        'chart_fallback_buffer_days',
        'is_active',
    ];

    protected $casts = [
        'gregorian_year' => 'integer',
        'hijri_year' => 'integer',
        'hijri_month' => 'integer',
        'sequence' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'default_fitrah_cash_per_jiwa' => 'integer',
        'default_fitrah_beras_per_jiwa' => 'decimal:2',
        'default_fidyah_per_hari' => 'integer',
        'default_fidyah_beras_per_hari' => 'decimal:2',
        'chart_starts_at' => 'date',
        'chart_ends_at' => 'date',
        'chart_fallback_buffer_days' => 'integer',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(ZakatTransaction::class, 'zakat_period_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->hijri_year) {
            return $this->label . ' (' . $this->hijri_year . ' H)';
        }

        return $this->label;
    }
}

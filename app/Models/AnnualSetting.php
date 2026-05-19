<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'default_fitrah_cash_per_jiwa',
        'default_fitrah_beras_per_jiwa',
        'default_fidyah_per_hari',
        'default_fidyah_beras_per_hari',
        'chart_starts_at',
        'chart_ends_at',
        'chart_fallback_buffer_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'default_fitrah_cash_per_jiwa' => 'integer',
        'default_fitrah_beras_per_jiwa' => 'decimal:2',
        'default_fidyah_per_hari' => 'integer',
        'default_fidyah_beras_per_hari' => 'decimal:2',
        'chart_starts_at' => 'date',
        'chart_ends_at' => 'date',
        'chart_fallback_buffer_days' => 'integer',
    ];
}

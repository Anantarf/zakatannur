<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAuditSummary extends Model
{
    protected $fillable = [
        'generated_by',
        'date_from',
        'date_to',
        'total_activities',
        'sensitive_activities_count',
        'summary',
        'recommendation',
        'context_snapshot',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'total_activities' => 'integer',
        'sensitive_activities_count' => 'integer',
        'context_snapshot' => 'array',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}

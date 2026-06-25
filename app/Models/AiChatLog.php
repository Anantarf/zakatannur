<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiChatLog extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'question',
        'question_md5',
        'intent',
        'context_summary',
        'answer',
        'source_type',
        'sentiment',
        'confidence_source',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

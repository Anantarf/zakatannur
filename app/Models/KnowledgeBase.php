<?php

namespace App\Models;

use App\Services\Chatbot\Knowledge\KnowledgeEmbeddingsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'keywords',
        'answer',
        'source_label',
        'actions',
        'is_active',
    ];

    protected $casts = [
        'keywords' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function toKnowledgeArray(): array
    {
        return [
            'id' => $this->slug,
            'title' => $this->title,
            'keywords' => $this->keywords ?? [],
            'answer' => $this->answer,
            'source_label' => $this->source_label,
            'actions' => [],
        ];
    }

    protected static function booted()
    {
        static::saved(function ($model) {
            app(KnowledgeEmbeddingsCache::class)->refreshCache();
        });

        static::deleted(function ($model) {
            app(KnowledgeEmbeddingsCache::class)->refreshCache();
        });
    }
}

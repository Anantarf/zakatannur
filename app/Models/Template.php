<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    public const TYPE_LETTERHEAD = 'letterhead';

    protected $fillable = [
        'template_type',
        'version',
        'is_active',
        'storage_path',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'uploaded_by',
    ];

    protected $casts = [
        'version' => 'integer',
        'is_active' => 'boolean',
        'file_size_bytes' => 'integer',
    ];
}

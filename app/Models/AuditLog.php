<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'actor_user_id' => 'integer',
    ];

    public function actorUser()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

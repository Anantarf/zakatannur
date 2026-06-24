<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotFeedback extends Model
{
    protected $table = 'chatbot_feedbacks';
    protected $fillable = ['session_id', 'message', 'rating', 'ip_address'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}

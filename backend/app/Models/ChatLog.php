<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'platform',
        'user_query',
        'bot_response',
        'intent',
        'major_name',
        'admission_year',
        'admission_method',
        'score',
        'province',
        'entities',
        'agent_steps',
        'retrieval_summary',
        'response_time',
    ];

    protected $casts = [
        'entities' => 'array',
        'agent_steps' => 'array',
        'retrieval_summary' => 'array',
        'score' => 'float',
        'response_time' => 'float',
    ];
}

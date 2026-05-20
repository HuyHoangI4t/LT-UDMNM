<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqQuestion extends Model
{
    protected $fillable = [
        'question',
        'category',
        'source_type',
        'knowledge_base_id',
    ];
}
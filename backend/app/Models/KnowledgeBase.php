<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'title',
        'url',
        'content',
        'pdf_links',
        'image_links',
        'embedding',
    ];

    protected $casts = [
        'pdf_links' => 'array',
        'image_links' => 'array',
    ];
}



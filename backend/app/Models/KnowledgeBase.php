<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'source_type',
        'title',
        'url',
        'content',
        'pdf_links',
        'image_links',
        'published_at',
        'embedding',
    ];

    protected $casts = [
        'pdf_links' => 'array',
        'image_links' => 'array',
        'published_at' => 'datetime',
    ];
}
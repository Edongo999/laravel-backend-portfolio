<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'content',
        'title_fr',
        'content_fr',
        'title_en',
        'content_en',
        'category',
        'image',
        'archived',
    ];

    protected $attributes = [
        'archived' => false,
    ];

    protected $casts = [
        'archived' => 'boolean',
    ];
}

<?php

namespace App\Models\Ess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EssAnnouncement extends Model
{
    use HasFactory;

    protected $table = 'ess_announcements';

    protected $fillable = [
        'title',
        'content',
        'category',
        'priority',
        'is_active',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}

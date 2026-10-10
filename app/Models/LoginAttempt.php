<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    public $timestamps = false;

    protected $fillable = [
        'ip_address',
        'identity',
        'attempted_at',
        'is_successful',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'is_successful' => 'boolean',
        ];
    }
}

<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrAbsenceCode extends Model
{
    use HasFactory;

    protected $table = 'hr_dtr_absence_codes';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_absence',
        'is_active',
    ];

    protected $casts = [
        'is_absence' => 'boolean',
        'is_active' => 'boolean',
    ];
}

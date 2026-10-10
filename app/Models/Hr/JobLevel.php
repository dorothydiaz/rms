<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLevel extends Model
{
    use HasFactory;

    protected $table = 'hr_job_levels';

    protected $fillable = [
        'name',
        'level',
        'description',
    ];
}

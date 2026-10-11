<?php

namespace App\Models\Hr;

use App\Traits\HasEntityPrimaryKey;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLevel extends Model
{
    use HasEntityPrimaryKey;

    protected $primaryKey = 'job_level_id';
    use HasFactory;

    protected $table = 'hr_job_levels';

    protected $fillable = [
        'name',
        'level',
        'description',
    ];
}

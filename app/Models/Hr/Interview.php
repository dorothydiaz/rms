<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $table = 'hr_interviews';

    protected $fillable = [
        'applicant_id',
        'interview_date',
        'interview_time',
        'interviewer_id',
        'interviewer_name',
        'interview_type',
        'interview_stage',
        'location_or_link',
        'instructions',
        'notes',
        'rating',
        'recommendation',
        'scorecard',
        'status',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
        'rating' => 'integer',
        'scorecard' => 'array',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_id');
    }
}

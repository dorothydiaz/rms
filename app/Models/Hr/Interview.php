<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'interview_date',
        'interviewer_id',
        'interviewer_name',
        'interview_type',
        'notes',
        'rating',
        'recommendation',
        'status',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
        'rating' => 'integer',
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

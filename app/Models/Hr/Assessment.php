<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $table = 'hr_assessments';

    protected $fillable = [
        'applicant_id',
        'assessment_type',
        'title',
        'assessment_date',
        'score',
        'passing_score',
        'result',
        'evaluator_id',
        'evaluator_name',
        'attachment_path',
        'remarks',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'score' => 'decimal:2',
        'passing_score' => 'decimal:2',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}

<?php

namespace App\Models\Ess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Hr\Employee;
use App\Models\User;

class EssRequest extends Model
{
    use HasFactory;

    protected $table = 'ess_requests';

    protected $fillable = [
        'request_number',
        'employee_id',
        'request_type',
        'details',
        'reason',
        'attachment_url',
        'status',
        'approval_step',
        'approver_id',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'details' => 'array',
        'approved_at' => 'datetime',
        'approval_step' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public static function generateRequestNumber(string $prefix = 'REQ'): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }
}

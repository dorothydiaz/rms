<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EssRequest extends Model
{
    use HasFactory;

    protected $table = 'ess_requests';

    protected $fillable = [
        'request_number',
        'employee_id',
        'request_type',
        'status',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'total_hours',
        'reason',
        'metadata',
        'attachment_path',
        'current_approver_id',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_hours' => 'decimal:2',
        'metadata' => 'array',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EssRequestLog::class, 'ess_request_id')->orderBy('created_at', 'asc');
    }

    public function scopeForEmployee(Builder $query, string $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'PENDING_APPROVAL');
    }

    public static function generateRequestNumber(string $prefix = 'REQ'): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $count);
    }
}

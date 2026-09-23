<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'employee_id',
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'date_of_birth',
        'gender',
        'civil_status',
        'nationality',
        'mobile_number',
        'email',
        'address',
        'photo',
        'branch_id',
        'department_id',
        'position_id',
        'supervisor_id',
        'date_hired',
        'employment_status',
        'employment_type',
        'date_of_regularization',
        'contract_start_date',
        'contract_end_date',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'tin',
        'basic_salary',
        'salary_type',
        'pay_frequency',
        'allowances',
        'other_compensation',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'date_hired' => 'date',
            'date_of_regularization' => 'date',
            'contract_start_date' => 'date',
            'contract_end_date' => 'date',
            'basic_salary' => 'decimal:2',
            'allowances' => 'decimal:2',
        ];
    }

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' ' . mb_substr($this->middle_name, 0, 1) . '.' : '';
        $suffix = $this->suffix ? ' ' . $this->suffix : '';
        return "{$this->first_name}{$middle} {$this->last_name}{$suffix}";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function employmentHistories(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class)->orderBy('effective_date', 'desc');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function trainingEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(PerformanceEvaluation::class);
    }

    /**
     * Scope query to branch based on current logged in user permissions.
     */
    public function scopeAccessibleBy(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        if ($user->isSuperAdmin() || $user->isHrAdmin()) {
            return $query;
        }

        if ($user->isManager() && $user->branch_id) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}

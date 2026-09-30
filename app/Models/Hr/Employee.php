<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'hr_employees';

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
        'company_id',
        'department_id',
        'position_id',
        'supervisor_id',
        'date_hired',
        'employment_status',
        'employment_type',
        'employment_source',
        'company_name',
        'agency_name',
        'company_agency_name',
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
        'assigned_department_ids',
        'assigned_branch_ids',
        'assigned_position_ids',
    ];

    protected $appends = [
        'has_multiple_departments',
        'all_department_ids',
        'assigned_department_names',
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
            'assigned_department_ids' => 'array',
            'assigned_branch_ids' => 'array',
            'assigned_position_ids' => 'array',
        ];
    }

    public function getAllDepartmentIdsAttribute(): array
    {
        $ids = is_array($this->assigned_department_ids) ? $this->assigned_department_ids : [];
        if ($this->department_id && !in_array($this->department_id, $ids)) {
            array_unshift($ids, $this->department_id);
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public function getAllBranchIdsAttribute(): array
    {
        $ids = is_array($this->assigned_branch_ids) ? $this->assigned_branch_ids : [];
        if ($this->branch_id && !in_array($this->branch_id, $ids)) {
            array_unshift($ids, $this->branch_id);
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public function getAllPositionIdsAttribute(): array
    {
        $ids = is_array($this->assigned_position_ids) ? $this->assigned_position_ids : [];
        if ($this->position_id && !in_array($this->position_id, $ids)) {
            array_unshift($ids, $this->position_id);
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public function getHasMultipleDepartmentsAttribute(): bool
    {
        return count($this->all_department_ids) > 1;
    }

    public function getAssignedDepartmentNamesAttribute(): array
    {
        if (empty($this->all_department_ids)) {
            return $this->department ? [$this->department->name] : [];
        }
        return Department::whereIn('id', $this->all_department_ids)->pluck('name')->toArray();
    }

    public function assignedDepartments()
    {
        return Department::whereIn('id', $this->all_department_ids)->get();
    }

    public function assignedBranches()
    {
        return Branch::whereIn('id', $this->all_branch_ids)->get();
    }

    public function assignedPositions()
    {
        return Position::whereIn('id', $this->all_position_ids)->get();
    }

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' ' . mb_substr($this->middle_name, 0, 1) . '.' : '';
        $suffix = $this->suffix ? ' ' . $this->suffix : '';
        return "{$this->first_name}{$middle} {$this->last_name}{$suffix}";
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!empty($this->photo)) {
            if (str_starts_with($this->photo, 'http://') || str_starts_with($this->photo, 'https://')) {
                return $this->photo;
            }
            if (str_starts_with($this->photo, 'storage/')) {
                return asset($this->photo);
            }
            return asset('storage/' . $this->photo);
        }
        return null;
    }

    public function getInitialsAttribute(): string
    {
        $first = mb_substr(trim($this->first_name ?? ''), 0, 1);
        $last = mb_substr(trim($this->last_name ?? ''), 0, 1);
        $init = strtoupper($first . $last);
        return $init ?: 'EM';
    }

    public function getCompanyOrAgencyAttribute(): string
    {
        if ($this->employment_source === 'Agency') {
            return $this->agency_name ?: ($this->company?->type === 'Agency' ? $this->company->name : ($this->company_agency_name ?: 'Agency'));
        }
        return $this->company_name ?: ($this->company?->name ?: ($this->company_agency_name ?: ($this->branch?->company?->name ?? 'Company')));
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'hr_employee_permissions');
    }

    public function getAssignedRoleAttribute(): ?Role
    {
        if ($this->user && $this->user->roles->isNotEmpty()) {
            return $this->user->roles->first();
        }

        $posName = strtolower($this->position?->name ?? '');
        if (str_contains($posName, 'manager')) {
            return Role::where('slug', 'restaurant-manager')->first();
        }
        if (str_contains($posName, 'cashier')) {
            return Role::where('slug', 'cashier')->first();
        }
        if (str_contains($posName, 'cook') || str_contains($posName, 'kitchen') || str_contains($posName, 'chef')) {
            return Role::where('slug', 'kitchen')->first();
        }
        return Role::where('slug', 'staff')->first();
    }

    public function hasDirectPermission(int|string $permission): bool
    {
        if (is_numeric($permission)) {
            return $this->permissions->contains('id', (int) $permission);
        }
        return $this->permissions->contains('slug', $permission);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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

    /**
     * Scope query to all active workforce employees
     * (includes Active, Probationary, On Leave, Suspended; excludes separated staff: Resigned, Terminated, Retired).
     */
    public function scopeActiveWorkforce(Builder $query): Builder
    {
        return $query->whereNotIn('employment_status', ['Resigned', 'Terminated', 'Retired']);
    }

    /**
     * Scope to active workforce as an alias.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('employment_status', ['Resigned', 'Terminated', 'Retired']);
    }
}

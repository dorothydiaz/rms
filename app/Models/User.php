<?php

namespace App\Models;

use App\Models\Hr\Branch;
use App\Models\Hr\Employee;
use App\Models\Hr\Permission;
use App\Models\Hr\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password',
        'role',
        'status',
        'branch_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->full_name ?? $this->username;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function isSuperAdmin(): bool
    {
        return strcasecmp($this->role, 'Admin') === 0 || $this->hasRole('super-admin');
    }

    public function isHrAdmin(): bool
    {
        return $this->isSuperAdmin() || strcasecmp($this->role, 'HR') === 0 || $this->hasRole('hr-admin');
    }

    public function isManager(): bool
    {
        return strcasecmp($this->role, 'Manager') === 0 || $this->hasRole('restaurant-manager');
    }

    public function isRestaurantManager(): bool
    {
        return $this->isManager();
    }

    public function isCashier(): bool
    {
        return strcasecmp($this->role, 'Cashier') === 0 || $this->hasRole('cashier');
    }

    public function isKitchen(): bool
    {
        return strcasecmp($this->role, 'Kitchen') === 0 || $this->hasRole('kitchen');
    }

    public function isStaff(): bool
    {
        return strcasecmp($this->role, 'Staff') === 0 || $this->hasRole('staff');
    }

    public function isActive(): bool
    {
        return strcasecmp($this->status, 'Active') === 0;
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 1. Check direct user permissions
        if ($this->relationLoaded('permissions')) {
            if ($this->permissions->contains('slug', $permissionSlug)) {
                return true;
            }
        } else {
            if ($this->permissions()->where('slug', $permissionSlug)->exists()) {
                return true;
            }
        }

        // 2. Check employee direct permissions if linked
        if ($this->employee) {
            if ($this->employee->relationLoaded('permissions')) {
                if ($this->employee->permissions->contains('slug', $permissionSlug)) {
                    return true;
                }
            } else {
                if ($this->employee->permissions()->where('slug', $permissionSlug)->exists()) {
                    return true;
                }
            }
        }

        // 3. Check role-assigned permissions
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionSlug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if user has access to a specific branch.
     * Super Admin and HR Admin have universal access.
     * Restaurant Managers only have access to their designated branch.
     */
    public function canAccessBranch(?int $branchId): bool
    {
        if ($this->isSuperAdmin() || $this->isHrAdmin()) {
            return true;
        }

        if ($this->isManager()) {
            return $branchId === null || (int)$this->branch_id === (int)$branchId;
        }

        return false;
    }

    /**
     * Check if user can view sensitive payroll and government numbers
     */
    public function canViewSensitiveData(): bool
    {
        return $this->isSuperAdmin() || $this->isHrAdmin();
    }
}

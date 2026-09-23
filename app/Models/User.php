<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'full_name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Convenience accessor for name -> full_name
     */
    public function getNameAttribute(): string
    {
        return $this->full_name ?? $this->username;
    }

    public function isAdmin(): bool
    {
        return strcasecmp($this->role, 'Admin') === 0;
    }

    public function isManager(): bool
    {
        return strcasecmp($this->role, 'Manager') === 0;
    }

    public function isCashier(): bool
    {
        return strcasecmp($this->role, 'Cashier') === 0;
    }

    public function isKitchen(): bool
    {
        return strcasecmp($this->role, 'Kitchen') === 0;
    }

    public function isStaff(): bool
    {
        return strcasecmp($this->role, 'Staff') === 0;
    }

    public function isActive(): bool
    {
        return strcasecmp($this->status, 'Active') === 0;
    }
}

<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'code',
        'tin',
        'email',
        'phone',
        'address',
        'logo',
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}

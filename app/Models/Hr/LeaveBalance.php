<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $table = 'hr_leave_balances';

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'beginning_balance',
        'earned',
        'used',
        'remaining',
        'encashed',
    ];

    protected $casts = [
        'year' => 'integer',
        'beginning_balance' => 'decimal:2',
        'earned' => 'decimal:2',
        'used' => 'decimal:2',
        'remaining' => 'decimal:2',
        'encashed' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}

<?php

namespace App\Models\Hr;

use App\Traits\HasEntityPrimaryKey;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollAdjustment extends Model
{
    use HasEntityPrimaryKey;

    protected $primaryKey = 'payroll_adjustment_id';
    use HasFactory;

    protected $table = 'hr_payroll_adjustments';

    protected $fillable = [
        'payroll_record_id',
        'adjustment_type',
        'name',
        'amount',
        'remarks',
        'approved_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payrollRecord()
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

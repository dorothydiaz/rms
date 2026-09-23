<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'payout_date',
        'pay_frequency',
        'status',
        'processed_by',
        'approved_by',
        'finalized_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payout_date' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function records()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function isFinalized(): bool
    {
        return $this->status === 'Finalized';
    }
}

<?php

namespace App\Models\Hr;

use App\Traits\HasEntityPrimaryKey;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasEntityPrimaryKey;

    protected $primaryKey = 'audit_log_id';
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_id',
        'ip_address',
        'previous_value',
        'new_value',
        'details',
        'created_at',
    ];

    protected $casts = [
        'previous_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

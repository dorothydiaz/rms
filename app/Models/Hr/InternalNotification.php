<?php

namespace App\Models\Hr;

use App\Traits\HasEntityPrimaryKey;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalNotification extends Model
{
    use HasEntityPrimaryKey;

    protected $primaryKey = 'internal_notification_id';
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role_target',
        'branch_id',
        'title',
        'message',
        'type',
        'link',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

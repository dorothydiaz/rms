<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EssRequestLog extends Model
{
    use HasFactory;

    protected $table = 'ess_request_logs';

    protected $fillable = [
        'ess_request_id',
        'action',
        'acted_by',
        'remarks',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(EssRequest::class, 'ess_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}

<?php

namespace App\Models\Ess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class EssHelpdeskMessage extends Model
{
    use HasFactory;

    protected $table = 'ess_helpdesk_messages';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachment_url',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(EssHelpdeskTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

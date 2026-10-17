<?php

namespace App\Models\Ess;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Hr\Employee;

class EssHelpdeskTicket extends Model
{
    use HasFactory;

    protected $table = 'ess_helpdesk_tickets';

    protected $fillable = [
        'ticket_number',
        'employee_id',
        'category',
        'subject',
        'priority',
        'status',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EssHelpdeskMessage::class, 'ticket_id');
    }

    public static function generateTicketNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('TKT-%s-%04d', $year, $count);
    }
}

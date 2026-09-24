<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftTemplate extends Model
{
    use HasFactory;

    protected $table = 'shift_templates';

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'is_overnight',
        'break_minutes',
        'color',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_overnight' => 'boolean',
            'break_minutes' => 'integer',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public static function formatShiftLabel(string $startTime, string $endTime): string
    {
        $code = \Carbon\Carbon::parse($startTime)->format('Hi');
        $startFmt = \Carbon\Carbon::parse($startTime)->format('gA');
        $endFmt = \Carbon\Carbon::parse($endTime)->format('gA');
        return "{$code} = {$startFmt} - {$endFmt}";
    }

    public function getFormattedLabelAttribute(): string
    {
        return self::formatShiftLabel($this->start_time, $this->end_time);
    }
}

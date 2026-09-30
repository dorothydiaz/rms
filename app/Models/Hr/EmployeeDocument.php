<?php

namespace App\Models\Hr;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_documents';

    protected $fillable = [
        'employee_id',
        'document_type',
        'document_name',
        'file_path',
        'expiry_date',
        'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTitleAttribute(): string
    {
        return $this->document_name ?? 'Untitled Document';
    }

    public function setTitleAttribute($value): void
    {
        $this->attributes['document_name'] = $value;
    }

    public function getFileNameAttribute(): string
    {
        return basename($this->file_path ?? '');
    }

    public function getFileUrlAttribute(): string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : '#';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiry_date && !$this->is_expired && $this->expiry_date->diffInDays(now()) <= 30;
    }

    public function getFileSizeAttribute(): int
    {
        if ($this->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->file_path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->size($this->file_path);
        }
        return 125000;
    }
}

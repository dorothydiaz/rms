<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessLegalDocument extends Model
{
    use HasFactory;

    protected $table = 'business_legal_documents';

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'document_category',
        'document_name',
        'document_number',
        'issuing_authority',
        'issue_date',
        'expiration_date',
        'file_path',
        'file_type',
        'file_size_kb',
        'status',
        'renewal_reminder_days',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiration_date' => 'date',
        'renewal_reminder_days' => 'integer',
        'file_size_kb' => 'integer',
    ];

    protected $appends = [
        'expiration_status',
        'days_until_expiration',
        'status_badge_class',
        'file_url',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getExpirationStatusAttribute(): string
    {
        if (!$this->expiration_date) {
            return 'No Expiry';
        }

        $now = Carbon::now()->startOfDay();
        $expiry = Carbon::parse($this->expiration_date)->startOfDay();

        if ($expiry->isPast()) {
            return 'Expired';
        }

        $reminderThreshold = $now->copy()->addDays($this->renewal_reminder_days ?: 30);
        if ($expiry->lte($reminderThreshold)) {
            return 'Expiring Soon';
        }

        return 'Active';
    }

    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expiration_date) {
            return null;
        }

        $now = Carbon::now()->startOfDay();
        $expiry = Carbon::parse($this->expiration_date)->startOfDay();
        return (int) $now->diffInDays($expiry, false);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->expiration_status) {
            'Active' => 'status-active',
            'Expiring Soon' => 'status-warning',
            'Expired' => 'status-danger',
            default => 'status-neutral',
        };
    }

    public function getFileUrlAttribute(): string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : '#';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '>=', now())
            ->whereDate('expiration_date', '<=', now()->addDays(30));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', now());
    }
}

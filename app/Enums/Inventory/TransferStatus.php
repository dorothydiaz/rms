<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum TransferStatus: string
{
    case DRAFT = 'DRAFT';
    case PENDING = 'PENDING';
    case REQUESTED = 'REQUESTED';
    case APPROVED = 'APPROVED';
    case IN_TRANSIT = 'IN_TRANSIT';
    case PARTIALLY_RECEIVED = 'PARTIALLY_RECEIVED';
    case COMPLETED = 'COMPLETED';
    case DISCREPANCY = 'DISCREPANCY';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::DRAFT => in_array($next, [self::REQUESTED, self::APPROVED, self::IN_TRANSIT, self::CANCELLED], true),
            self::PENDING, self::REQUESTED => in_array($next, [self::APPROVED, self::IN_TRANSIT, self::REJECTED, self::CANCELLED], true),
            self::APPROVED => in_array($next, [self::IN_TRANSIT, self::REJECTED, self::CANCELLED], true),
            self::IN_TRANSIT => in_array($next, [self::PARTIALLY_RECEIVED, self::COMPLETED, self::DISCREPANCY], true),
            self::PARTIALLY_RECEIVED => in_array($next, [self::PARTIALLY_RECEIVED, self::COMPLETED, self::DISCREPANCY], true),
            self::COMPLETED, self::DISCREPANCY, self::REJECTED, self::CANCELLED => false,
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::PENDING,
            self::REQUESTED,
            self::APPROVED,
            self::IN_TRANSIT,
            self::PARTIALLY_RECEIVED,
        ], true);
    }

    public function isApprovable(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING, self::REQUESTED], true);
    }

    public function isDispatchable(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING, self::REQUESTED, self::APPROVED], true);
    }

    public function isReceivable(): bool
    {
        return in_array($this, [self::IN_TRANSIT, self::PARTIALLY_RECEIVED], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING, self::REQUESTED, self::APPROVED], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING, self::REQUESTED => 'Requested',
            self::APPROVED => 'Approved & Reserved',
            self::IN_TRANSIT => 'In Transit',
            self::PARTIALLY_RECEIVED => 'Partially Received',
            self::COMPLETED => 'Completed',
            self::DISCREPANCY => 'Received w/ Variance',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }
}

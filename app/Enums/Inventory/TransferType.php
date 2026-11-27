<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum TransferType: string
{
    case BRANCH_REQUEST = 'BRANCH_REQUEST';
    case CUSTOM_PUSH = 'CUSTOM_PUSH';
    case COMMISSARY_REPLENISHMENT = 'COMMISSARY_REPLENISHMENT';
    case BACKORDER_REQUEST = 'BACKORDER_REQUEST';

    public function label(): string
    {
        return match ($this) {
            self::BRANCH_REQUEST => 'Branch Request (Pull)',
            self::CUSTOM_PUSH => 'Direct Transfer (Push)',
            self::COMMISSARY_REPLENISHMENT => 'Auto Replenishment',
            self::BACKORDER_REQUEST => 'Backorder Request',
        };
    }
}

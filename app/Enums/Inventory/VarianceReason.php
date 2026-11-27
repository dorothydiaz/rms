<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum VarianceReason: string
{
    case DAMAGED_IN_TRANSIT = 'DAMAGED_IN_TRANSIT';
    case SHORT_PICKED = 'SHORT_PICKED';
    case SPOILED_TEMPERATURE = 'SPOILED_TEMPERATURE';
    case LOST_IN_TRANSIT = 'LOST_IN_TRANSIT';
    case OVERAGE_RETURNED = 'OVERAGE_RETURNED';

    public function label(): string
    {
        return match ($this) {
            self::DAMAGED_IN_TRANSIT => 'Damaged in Transit',
            self::SHORT_PICKED => 'Short-Picked by Source',
            self::SPOILED_TEMPERATURE => 'Spoiled / Temp Breach',
            self::LOST_IN_TRANSIT => 'Lost / Missing in Transit',
            self::OVERAGE_RETURNED => 'Overage Returned',
        };
    }
}

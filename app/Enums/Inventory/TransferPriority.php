<?php

declare(strict_types=1);

namespace App\Enums\Inventory;

enum TransferPriority: string
{
    case NORMAL = 'NORMAL';
    case HIGH = 'HIGH';
    case URGENT = 'URGENT';
    case EMERGENCY = 'EMERGENCY';
}

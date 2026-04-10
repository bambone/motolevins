<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum ExternalBusyEffect: string
{
    case HardBlock = 'hard_block';

    case SoftWarning = 'soft_warning';

    case InformationalOnly = 'informational_only';
}

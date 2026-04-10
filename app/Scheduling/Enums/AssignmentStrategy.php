<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum AssignmentStrategy: string
{
    case ManualAfterRequest = 'manual_after_request';

    case FirstAvailable = 'first_available';

    case RoundRobin = 'round_robin';

    case PriorityOrder = 'priority_order';
}

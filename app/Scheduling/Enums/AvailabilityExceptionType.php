<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum AvailabilityExceptionType: string
{
    case Open = 'open';

    case Closed = 'closed';
}

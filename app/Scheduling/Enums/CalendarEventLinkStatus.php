<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum CalendarEventLinkStatus: string
{
    case Active = 'active';

    case Cancelled = 'cancelled';

    case Orphaned = 'orphaned';

    case Error = 'error';
}

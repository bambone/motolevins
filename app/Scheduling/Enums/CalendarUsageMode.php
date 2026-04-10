<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum CalendarUsageMode: string
{
    case Disabled = 'disabled';

    case ReadBusyOnly = 'read_busy_only';

    case ReadBusyWriteEvents = 'read_busy_write_events';

    case WriteOnly = 'write_only';
}

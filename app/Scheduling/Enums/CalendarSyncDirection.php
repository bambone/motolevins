<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum CalendarSyncDirection: string
{
    case WriteOnly = 'write_only';

    case ReadWrite = 'read_write';
}

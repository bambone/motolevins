<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum ManualBusySource: string
{
    case Operator = 'operator';

    case Migration = 'migration';

    case SyncRepair = 'sync_repair';
}

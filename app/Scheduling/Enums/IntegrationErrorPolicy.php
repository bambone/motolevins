<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum IntegrationErrorPolicy: string
{
    case IgnoreExternal = 'ignore_external';

    case BlockScheduling = 'block_scheduling';

    case WarnOnly = 'warn_only';
}

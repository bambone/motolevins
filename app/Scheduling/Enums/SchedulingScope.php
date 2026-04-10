<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum SchedulingScope: string
{
    case Tenant = 'tenant';

    case Platform = 'platform';
}

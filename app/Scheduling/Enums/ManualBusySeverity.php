<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum ManualBusySeverity: string
{
    case Hard = 'hard';

    case Soft = 'soft';
}

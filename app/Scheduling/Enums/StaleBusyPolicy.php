<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum StaleBusyPolicy: string
{
    case TreatAsOk = 'treat_as_ok';

    case WarnOnly = 'warn_only';

    case BlockNewSlots = 'block_new_slots';
}

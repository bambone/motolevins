<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum UnconfirmedRequestsPolicy: string
{
    case Ignore = 'ignore';

    case HoldOnly = 'hold_only';

    case PendingIsBusy = 'pending_is_busy';

    case PendingAndConfirmedAreBusy = 'pending_and_confirmed_are_busy';

    case ConfirmedOnly = 'confirmed_only';
}

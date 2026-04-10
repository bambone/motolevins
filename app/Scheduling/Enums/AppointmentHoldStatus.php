<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum AppointmentHoldStatus: string
{
    case Hold = 'hold';

    case Pending = 'pending';

    case Confirmed = 'confirmed';

    case Cancelled = 'cancelled';

    case Expired = 'expired';
}

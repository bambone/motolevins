<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum SchedulingTargetType: string
{
    case BookableService = 'bookable_service';

    case RentalUnit = 'rental_unit';

    case Branch = 'branch';

    case City = 'city';

    case Generic = 'generic';
}

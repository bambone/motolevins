<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum BookableServiceLinkType: string
{
    case Standalone = 'standalone';

    case Motorcycle = 'motorcycle';

    case RentalUnit = 'rental_unit';
}

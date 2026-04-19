<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

enum TariffKind: string
{
    case FixedPerRental = 'fixed_per_rental';
    case FixedPerDay = 'fixed_per_day';
    case FixedPerHourBlock = 'fixed_per_hour_block';
    case OnRequest = 'on_request';
    case Informational = 'informational';
}

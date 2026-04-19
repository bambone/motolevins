<?php

declare(strict_types=1);

namespace App\MotorcyclePricing;

enum ApplicabilityMode: string
{
    case Always = 'always';
    case DurationRangeDays = 'duration_range_days';
    case DurationMinDays = 'duration_min_days';
    case ManualOnly = 'manual_only';
}

<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum MatchMode: string
{
    case EntireCalendar = 'entire_calendar';

    case SummaryContains = 'summary_contains';

    case SummaryRegex = 'summary_regex';

    case LocationContains = 'location_contains';

    case ManualAssignment = 'manual_assignment';
}

<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum OccupancyMappingType: string
{
    case CalendarToTarget = 'calendar_to_target';

    case CalendarToResource = 'calendar_to_resource';

    case EventRuleToTarget = 'event_rule_to_target';
}

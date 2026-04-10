<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum OccupancyScopeMode: string
{
    case PerUnit = 'per_unit';

    case SharedPool = 'shared_pool';

    case BranchLevel = 'branch_level';

    case CityLevel = 'city_level';

    case Generic = 'generic';
}

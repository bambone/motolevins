<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum SchedulingResourceType: string
{
    case Person = 'person';

    case Team = 'team';

    case Room = 'room';

    case Vehicle = 'vehicle';

    case AssetPool = 'asset_pool';

    case Branch = 'branch';

    case Generic = 'generic';
}

<?php

declare(strict_types=1);

namespace App\Scheduling\Enums;

enum TentativeEventsPolicy: string
{
    case TreatAsBusy = 'treat_as_busy';

    case TreatAsFree = 'treat_as_free';

    case ProviderDefault = 'provider_default';
}

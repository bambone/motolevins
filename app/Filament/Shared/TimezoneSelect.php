<?php

declare(strict_types=1);

namespace App\Filament\Shared;

use App\Filament\Tenant\Resources\SchedulingResourceResource;
use App\Scheduling\SchedulingTimezoneOptions;
use Filament\Forms\Components\Select;

/**
 * Единый UX выбора IANA-часового пояса (как у {@see SchedulingResourceResource}).
 */
final class TimezoneSelect
{
    public static function make(string $name = 'timezone'): Select
    {
        return Select::make($name)
            ->label('Часовой пояс')
            ->searchable()
            ->options(fn () => SchedulingTimezoneOptions::all())
            ->helperText(
                'Выбор из списка. В поиске — город, регион или смещение: например «2» найдёт UTC+02, UTC−02 и зоны вроде +02:30.'
            );
    }
}

<?php

namespace App\Filament\Tenant\Resources\CalendarOccupancyMappingResource\Pages;

use App\Filament\Tenant\Resources\CalendarOccupancyMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCalendarOccupancyMappings extends ListRecords
{
    protected static string $resource = CalendarOccupancyMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

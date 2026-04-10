<?php

namespace App\Filament\Tenant\Resources\CalendarConnectionResource\Pages;

use App\Filament\Tenant\Resources\CalendarConnectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCalendarConnections extends ListRecords
{
    protected static string $resource = CalendarConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

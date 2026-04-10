<?php

namespace App\Filament\Tenant\Resources\AvailabilityExceptionResource\Pages;

use App\Filament\Tenant\Resources\AvailabilityExceptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAvailabilityExceptions extends ListRecords
{
    protected static string $resource = AvailabilityExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

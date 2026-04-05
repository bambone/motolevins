<?php

namespace App\Filament\Tenant\Resources\NotificationDestinationResource\Pages;

use App\Filament\Tenant\Resources\NotificationDestinationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationDestinations extends ListRecords
{
    protected static string $resource = NotificationDestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

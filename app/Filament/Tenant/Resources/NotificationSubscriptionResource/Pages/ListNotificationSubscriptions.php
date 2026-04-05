<?php

namespace App\Filament\Tenant\Resources\NotificationSubscriptionResource\Pages;

use App\Filament\Tenant\Resources\NotificationSubscriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationSubscriptions extends ListRecords
{
    protected static string $resource = NotificationSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

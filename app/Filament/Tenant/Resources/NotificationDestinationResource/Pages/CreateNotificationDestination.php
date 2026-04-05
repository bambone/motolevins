<?php

namespace App\Filament\Tenant\Resources\NotificationDestinationResource\Pages;

use App\Filament\Tenant\Resources\NotificationDestinationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationDestination extends CreateRecord
{
    protected static string $resource = NotificationDestinationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = currentTenant();
        $data['tenant_id'] = $tenant?->id;

        return $data;
    }
}

<?php

namespace App\Filament\Tenant\Resources\NotificationDestinationResource\Pages;

use App\Filament\Tenant\Resources\NotificationDestinationResource;
use App\Tenant\Filament\TenantCabinetUserPicker;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationDestination extends CreateRecord
{
    protected static string $resource = NotificationDestinationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = currentTenant();
        $data['tenant_id'] = $tenant?->id;

        if (($data['user_id'] ?? null) !== null && $data['user_id'] !== '' && $tenant !== null) {
            TenantCabinetUserPicker::assertUserBelongsToCabinetTeam($tenant->id, (int) $data['user_id']);
        }

        return $data;
    }
}

<?php

namespace App\Filament\Tenant\Resources\NotificationDestinationResource\Pages;

use App\Filament\Tenant\Resources\NotificationDestinationResource;
use App\Tenant\Filament\TenantCabinetUserPicker;
use Filament\Resources\Pages\EditRecord;

class EditNotificationDestination extends EditRecord
{
    protected static string $resource = NotificationDestinationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['user_id'] ?? null) !== null && $data['user_id'] !== '') {
            $tenant = currentTenant();
            if ($tenant !== null) {
                TenantCabinetUserPicker::assertUserBelongsToCabinetTeam($tenant->id, (int) $data['user_id']);
            }
        }

        return $data;
    }
}

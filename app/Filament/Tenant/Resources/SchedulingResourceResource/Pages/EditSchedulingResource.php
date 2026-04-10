<?php

namespace App\Filament\Tenant\Resources\SchedulingResourceResource\Pages;

use App\Filament\Tenant\Resources\SchedulingResourceResource;
use App\Tenant\Filament\TenantCabinetUserPicker;
use Filament\Resources\Pages\EditRecord;

class EditSchedulingResource extends EditRecord
{
    protected static string $resource = SchedulingResourceResource::class;

    public function getTitle(): string
    {
        return 'Редактировать ресурс';
    }

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

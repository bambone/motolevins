<?php

namespace App\Filament\Tenant\Resources\SchedulingResourceResource\Pages;

use App\Filament\Tenant\Resources\SchedulingResourceResource;
use App\Scheduling\Enums\SchedulingScope;
use App\Tenant\Filament\TenantCabinetUserPicker;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedulingResource extends CreateRecord
{
    protected static string $resource = SchedulingResourceResource::class;

    public function getTitle(): string
    {
        return 'Создать ресурс';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $tenant = currentTenant();
        $data['tenant_id'] = $tenant?->id;

        if (($data['user_id'] ?? null) !== null && $data['user_id'] !== '' && $tenant !== null) {
            TenantCabinetUserPicker::assertUserBelongsToCabinetTeam($tenant->id, (int) $data['user_id']);
        }

        return $data;
    }
}

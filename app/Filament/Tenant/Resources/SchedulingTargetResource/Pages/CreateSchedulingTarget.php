<?php

namespace App\Filament\Tenant\Resources\SchedulingTargetResource\Pages;

use App\Filament\Tenant\Resources\SchedulingTargetResource;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Resources\Pages\CreateRecord;

class CreateSchedulingTarget extends CreateRecord
{
    protected static string $resource = SchedulingTargetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $data['tenant_id'] = currentTenant()?->id;

        return $data;
    }
}

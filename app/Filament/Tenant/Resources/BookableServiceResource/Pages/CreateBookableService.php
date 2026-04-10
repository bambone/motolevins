<?php

namespace App\Filament\Tenant\Resources\BookableServiceResource\Pages;

use App\Filament\Tenant\Resources\BookableServiceResource;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Resources\Pages\CreateRecord;

class CreateBookableService extends CreateRecord
{
    protected static string $resource = BookableServiceResource::class;

    public function getTitle(): string
    {
        return 'Создать услугу';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $data['tenant_id'] = currentTenant()?->id;

        return $data;
    }
}

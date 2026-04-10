<?php

namespace App\Filament\Tenant\Resources\CalendarConnectionResource\Pages;

use App\Filament\Tenant\Resources\CalendarConnectionResource;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendarConnection extends CreateRecord
{
    protected static string $resource = CalendarConnectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $data['tenant_id'] = currentTenant()?->id;

        return $data;
    }
}

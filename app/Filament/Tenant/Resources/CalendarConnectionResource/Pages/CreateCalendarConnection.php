<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\CalendarConnectionResource\Pages;

use App\Filament\Tenant\Resources\CalendarConnectionResource;
use App\Filament\Tenant\Support\AssertTenantOwnedIds;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Resources\Pages\CreateRecord;

class CreateCalendarConnection extends CreateRecord
{
    protected static string $resource = CalendarConnectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('scheduling_resource_id', $data)) {
            AssertTenantOwnedIds::assertOptionalSchedulingResourceId($data['scheduling_resource_id'] ?? null);
        }

        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $data['tenant_id'] = currentTenant()?->id;

        return $data;
    }
}

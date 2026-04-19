<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AvailabilityExceptionResource\Pages;

use App\Filament\Tenant\Resources\AvailabilityExceptionResource;
use App\Filament\Tenant\Support\AssertTenantOwnedIds;
use Filament\Resources\Pages\EditRecord;

class EditAvailabilityException extends EditRecord
{
    protected static string $resource = AvailabilityExceptionResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('scheduling_resource_id', $data) && $data['scheduling_resource_id'] !== null && $data['scheduling_resource_id'] !== '') {
            AssertTenantOwnedIds::assertSchedulingResourcesForCurrentTenant(
                [(int) $data['scheduling_resource_id']],
                'scheduling_resource_id',
            );
        }
        if (array_key_exists('scheduling_target_id', $data)) {
            AssertTenantOwnedIds::assertOptionalSchedulingTargetId($data['scheduling_target_id'] ?? null);
        }
        if (array_key_exists('bookable_service_id', $data)) {
            AssertTenantOwnedIds::assertOptionalBookableServiceId($data['bookable_service_id'] ?? null);
        }

        return $data;
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AvailabilityExceptionResource\Pages;

use App\Filament\Tenant\Resources\AvailabilityExceptionResource;
use App\Filament\Tenant\Support\AssertTenantOwnedIds;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAvailabilityException extends CreateRecord
{
    protected static string $resource = AvailabilityExceptionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('scheduling_resource_id', $data) && $data['scheduling_resource_id'] !== null && $data['scheduling_resource_id'] !== '') {
            AssertTenantOwnedIds::assertSchedulingResourcesForCurrentTenant(
                [(int) $data['scheduling_resource_id']],
                'scheduling_resource_id',
            );
        }
        AssertTenantOwnedIds::assertOptionalSchedulingTargetId($data['scheduling_target_id'] ?? null);
        AssertTenantOwnedIds::assertOptionalBookableServiceId($data['bookable_service_id'] ?? null);

        $data['created_by'] = Auth::id();

        return $data;
    }
}

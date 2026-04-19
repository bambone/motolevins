<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ManualBusyBlockResource\Pages;

use App\Filament\Tenant\Resources\ManualBusyBlockResource;
use App\Filament\Tenant\Support\AssertTenantOwnedIds;
use Filament\Resources\Pages\EditRecord;

class EditManualBusyBlock extends EditRecord
{
    protected static string $resource = ManualBusyBlockResource::class;

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

        return $data;
    }
}

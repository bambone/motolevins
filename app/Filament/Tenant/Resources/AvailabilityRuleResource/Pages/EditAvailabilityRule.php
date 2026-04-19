<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AvailabilityRuleResource\Pages;

use App\Filament\Tenant\Resources\AvailabilityRuleResource;
use App\Filament\Tenant\Support\AssertTenantOwnedIds;
use Filament\Resources\Pages\EditRecord;

class EditAvailabilityRule extends EditRecord
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('scheduling_resource_id', $data) && $data['scheduling_resource_id'] !== null && $data['scheduling_resource_id'] !== '') {
            AssertTenantOwnedIds::assertSchedulingResourcesForCurrentTenant(
                [(int) $data['scheduling_resource_id']],
                'scheduling_resource_id',
            );
        }

        return $data;
    }
}

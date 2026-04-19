<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ManualBusyBlockResource\Pages;

use App\Filament\Tenant\Resources\ManualBusyBlockResource;
use App\Filament\Tenant\Support\AssertTenantOwnedIds;
use App\Scheduling\Enums\SchedulingScope;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateManualBusyBlock extends CreateRecord
{
    protected static string $resource = ManualBusyBlockResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (array_key_exists('scheduling_resource_id', $data) && $data['scheduling_resource_id'] !== null && $data['scheduling_resource_id'] !== '') {
            AssertTenantOwnedIds::assertSchedulingResourcesForCurrentTenant(
                [(int) $data['scheduling_resource_id']],
                'scheduling_resource_id',
            );
        }
        AssertTenantOwnedIds::assertOptionalSchedulingTargetId($data['scheduling_target_id'] ?? null);

        $data['scheduling_scope'] = SchedulingScope::Tenant;
        $data['tenant_id'] = currentTenant()?->id;
        $data['created_by'] = Auth::id();

        return $data;
    }
}

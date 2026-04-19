<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Support;

final class AssertSchedulingTargetSelectedResources
{
    /**
     * @param  list<int|string>  $ids
     */
    public static function forTenantForm(array $ids): void
    {
        AssertTenantOwnedIds::assertSchedulingResourcesForCurrentTenant($ids, 'schedulingResources');
    }
}

<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\Tenant;

/**
 * Commercial / feature gating for scheduling and calendar integrations (separate from domain services).
 */
final class SchedulingEntitlementService
{
    public function tenantCanUseScheduling(Tenant $tenant): bool
    {
        return (bool) $tenant->scheduling_module_enabled;
    }

    public function tenantCanUseCalendarIntegrations(Tenant $tenant): bool
    {
        if (! $this->tenantCanUseScheduling($tenant)) {
            return false;
        }

        return (bool) $tenant->calendar_integrations_enabled;
    }

    /**
     * Single source of truth: tenant may edit linked online-booking on Motorcycle / RentalUnit cards.
     * When false, UI shows locked tab/summary but must not persist linked payload (see Edit pages).
     */
    public function tenantCanConfigureLinkedScheduling(Tenant $tenant): bool
    {
        return $this->tenantCanUseScheduling($tenant);
    }
}

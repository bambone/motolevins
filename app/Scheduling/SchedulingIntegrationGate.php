<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\CalendarConnection;
use App\Models\Tenant;
use App\Scheduling\Enums\IntegrationErrorPolicy;
use App\Scheduling\Enums\SchedulingScope;

/**
 * Политика ошибки интеграции календаря (отдельно от stale_busy_policy).
 */
final class SchedulingIntegrationGate
{
    public function tenantHasCalendarIntegrationErrors(Tenant $tenant): bool
    {
        return CalendarConnection::query()
            ->where('scheduling_scope', SchedulingScope::Tenant)
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->whereNotNull('last_error')
            ->exists();
    }

    public function blocksPublicAppointmentSlots(Tenant $tenant): bool
    {
        if ($tenant->scheduling_integration_error_policy !== IntegrationErrorPolicy::BlockScheduling) {
            return false;
        }

        return $this->tenantHasCalendarIntegrationErrors($tenant);
    }

    /**
     * @return list<string>
     */
    public function warningCodesForTenant(Tenant $tenant): array
    {
        if (! $this->tenantHasCalendarIntegrationErrors($tenant)) {
            return [];
        }

        return match ($tenant->scheduling_integration_error_policy) {
            IntegrationErrorPolicy::WarnOnly => ['scheduling_calendar_integration_error'],
            IntegrationErrorPolicy::IgnoreExternal => [],
            IntegrationErrorPolicy::BlockScheduling => [],
        };
    }
}

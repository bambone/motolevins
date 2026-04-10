<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\BookableService;
use App\Models\CalendarSubscription;
use App\Models\PlatformSetting;
use App\Models\SchedulingResource;
use App\Models\SchedulingTarget;
use App\Models\Tenant;
use App\Scheduling\Enums\CalendarUsageMode;

/**
 * Resolves which calendar subscription should receive outbound writes.
 * Order: service override → target → resource → tenant default → platform default → null.
 */
final class WriteCalendarResolver
{
    public function __construct(
        private readonly SchedulingEntitlementService $entitlements,
    ) {}

    public function resolveSubscription(
        ?BookableService $service,
        ?SchedulingTarget $target,
        ?SchedulingResource $resource,
        ?Tenant $tenant,
    ): ?CalendarSubscription {
        if ($tenant !== null && ! $this->entitlements->tenantCanUseCalendarIntegrations($tenant)) {
            return null;
        }

        if ($target !== null && ! $target->auto_write_to_calendar_enabled) {
            return null;
        }

        if ($target !== null) {
            $mode = $target->calendar_usage_mode;
            if (! in_array($mode, [CalendarUsageMode::ReadBusyWriteEvents, CalendarUsageMode::WriteOnly], true)) {
                return null;
            }
        }

        $id = $service?->default_write_calendar_subscription_id
            ?? $target?->default_write_calendar_subscription_id
            ?? $resource?->default_write_calendar_subscription_id
            ?? $tenant?->scheduling_default_write_calendar_subscription_id;

        if ($id === null) {
            $platformId = PlatformSetting::get('scheduling.default_write_calendar_subscription_id', null);
            if (is_numeric($platformId) && (int) $platformId > 0) {
                $id = (int) $platformId;
            }
        }

        if ($id === null) {
            return null;
        }

        $sub = CalendarSubscription::query()->find($id);
        if ($sub === null || ! $sub->is_active || ! $sub->use_for_write) {
            return null;
        }

        $connection = $sub->calendarConnection;
        if ($connection === null || ! $connection->is_active) {
            return null;
        }

        return $sub;
    }
}

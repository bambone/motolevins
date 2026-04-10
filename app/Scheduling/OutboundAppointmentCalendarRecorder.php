<?php

declare(strict_types=1);

namespace App\Scheduling;

use App\Models\AppointmentHold;
use App\Models\CalendarEventLink;
use App\Models\CrmRequest;
use App\Models\Tenant;
use App\Scheduling\Enums\CalendarEventLinkStatus;
use App\Scheduling\Enums\CalendarSyncDirection;

/**
 * Records intent to write a CRM-linked event to the resolved calendar; provider adapters perform the actual API call.
 */
final class OutboundAppointmentCalendarRecorder
{
    public function __construct(
        private readonly WriteCalendarResolver $writeCalendarResolver,
    ) {}

    public function recordForHold(Tenant $tenant, AppointmentHold $hold, CrmRequest $crm): void
    {
        $service = $hold->bookableService;
        if ($service === null) {
            return;
        }

        $target = $service->schedulingTarget;
        if ($target === null) {
            return;
        }

        $resource = $hold->schedulingResource;
        $subscription = $this->writeCalendarResolver->resolveSubscription($service, $target, $resource, $tenant);
        if ($subscription === null) {
            return;
        }

        CalendarEventLink::query()->create([
            'calendar_subscription_id' => $subscription->id,
            'scheduling_resource_id' => $resource?->id,
            'linkable_type' => $crm->getMorphClass(),
            'linkable_id' => $crm->id,
            'external_calendar_id' => $subscription->external_calendar_id,
            'external_event_id' => null,
            'external_event_uid' => null,
            'provider_etag' => null,
            'sync_direction' => CalendarSyncDirection::WriteOnly,
            'link_status' => CalendarEventLinkStatus::Active,
            'last_synced_at' => null,
            'last_error' => null,
        ]);
    }
}

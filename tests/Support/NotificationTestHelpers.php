<?php

namespace Tests\Support;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\Models\NotificationSubscription;
use App\Models\Tenant;
use App\NotificationCenter\NotificationChannelType;
use App\NotificationCenter\NotificationDestinationStatus;
use App\NotificationCenter\NotificationPayloadDto;

trait NotificationTestHelpers
{
    protected function createNotificationTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Notif tenant',
            'slug' => 'ntf-'.substr(uniqid('', true), -12),
            'status' => 'active',
        ], $overrides));
    }

    protected function createSharedInAppDestination(Tenant $tenant, array $overrides = []): NotificationDestination
    {
        return NotificationDestination::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'name' => 'In-app shared',
            'type' => NotificationChannelType::InApp->value,
            'status' => NotificationDestinationStatus::Verified->value,
            'is_shared' => true,
            'config_json' => [],
        ], $overrides));
    }

    protected function createPersonalInAppDestination(Tenant $tenant, int $userId, array $overrides = []): NotificationDestination
    {
        return NotificationDestination::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'user_id' => $userId,
            'name' => 'In-app personal',
            'type' => NotificationChannelType::InApp->value,
            'status' => NotificationDestinationStatus::Verified->value,
            'is_shared' => false,
            'config_json' => [],
        ], $overrides));
    }

    protected function attachDestinationsToSubscription(
        NotificationSubscription $subscription,
        NotificationDestination $destination,
        array $pivot = [],
    ): void {
        $subscription->destinations()->attach($destination->id, array_merge([
            'delivery_mode' => 'immediate',
            'delay_seconds' => null,
            'order_index' => 0,
            'is_enabled' => true,
        ], $pivot));
    }

    protected function samplePayload(): NotificationPayloadDto
    {
        return new NotificationPayloadDto('Hello', 'World', null, null, ['k' => 1]);
    }

    protected function assertDeliveryForTenant(int $tenantId, int $count = 1): void
    {
        $this->assertSame($count, NotificationDelivery::query()->where('tenant_id', $tenantId)->count());
    }

    protected function makeBareNotificationEvent(Tenant $tenant, string $eventKey = 'crm_request.created'): NotificationEvent
    {
        return NotificationEvent::factory()->create([
            'tenant_id' => $tenant->id,
            'event_key' => $eventKey,
            'subject_type' => 'CrmRequest',
            'subject_id' => 1,
            'severity' => 'normal',
            'dedupe_key' => null,
            'payload_json' => $this->samplePayload()->toArray(),
            'occurred_at' => now(),
        ]);
    }
}

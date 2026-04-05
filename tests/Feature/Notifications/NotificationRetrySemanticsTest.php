<?php

namespace Tests\Feature\Notifications;

use App\Jobs\DispatchNotificationDeliveryJob;
use App\Models\NotificationDelivery;
use App\Models\NotificationDeliveryAttempt;
use App\Models\NotificationEvent;
use App\NotificationCenter\NotificationChannelDriverFactory;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\Services\CurrentTenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class NotificationRetrySemanticsTest extends TestCase
{
    use NotificationTestHelpers;
    use RefreshDatabase;

    public function test_second_handle_creates_new_attempt_same_event_and_delivery(): void
    {
        $tenant = $this->createNotificationTenant();
        $dest = $this->createSharedInAppDestination($tenant);
        $event = NotificationEvent::factory()->create([
            'tenant_id' => $tenant->id,
            'event_key' => 'crm_request.created',
            'subject_type' => 'CrmRequest',
            'subject_id' => 1,
            'severity' => 'normal',
            'dedupe_key' => null,
            'payload_json' => $this->samplePayload()->toArray(),
            'occurred_at' => now(),
        ]);

        $delivery = NotificationDelivery::factory()->create([
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'destination_id' => $dest->id,
            'channel_type' => $dest->type,
            'status' => NotificationDeliveryStatus::Queued->value,
            'queued_at' => now(),
        ]);

        $payloadBefore = $event->fresh()->payload_json;

        app(CurrentTenantManager::class)->setTenant($tenant);
        $factory = app(NotificationChannelDriverFactory::class);

        $job = new DispatchNotificationDeliveryJob((int) $delivery->id);
        $job->handle(app(CurrentTenantManager::class), $factory);

        $delivery->refresh();
        $this->assertSame(NotificationDeliveryStatus::Delivered->value, $delivery->status);
        $this->assertSame(1, NotificationDeliveryAttempt::query()->where('delivery_id', $delivery->id)->count());

        NotificationDelivery::query()->whereKey($delivery->id)->update([
            'status' => NotificationDeliveryStatus::Queued->value,
            'delivered_at' => null,
            'sent_at' => null,
            'failed_at' => null,
            'error_message' => null,
        ]);

        $job2 = new DispatchNotificationDeliveryJob((int) $delivery->id);
        $job2->handle(app(CurrentTenantManager::class), $factory);

        $this->assertSame(1, NotificationEvent::query()->where('id', $event->id)->count());
        $this->assertSame(1, NotificationDelivery::query()->where('id', $delivery->id)->count());
        $this->assertSame(2, NotificationDeliveryAttempt::query()->where('delivery_id', $delivery->id)->count());
        $this->assertEquals($payloadBefore, $event->fresh()->payload_json);
    }
}

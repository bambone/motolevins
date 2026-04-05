<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationDelivery;
use App\Models\NotificationEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class NotificationInAppDeliveryTest extends TestCase
{
    use NotificationTestHelpers;
    use RefreshDatabase;

    public function test_read_at_can_be_set_on_in_app_delivery_row(): void
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
            'status' => 'delivered',
            'read_at' => null,
        ]);

        $at = now()->startOfSecond();
        $delivery->update(['read_at' => $at]);
        $delivery->refresh();

        $this->assertNotNull($delivery->read_at);
        $this->assertTrue($at->equalTo($delivery->read_at));
    }
}

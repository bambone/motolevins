<?php

namespace Tests\Feature\Notifications;

use App\Models\CrmRequest;
use App\Models\NotificationDelivery;
use App\Models\NotificationSubscription;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\Presenters\CrmRequestNotificationPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class NotificationTenantIsolationTest extends TestCase
{
    use NotificationTestHelpers;
    use RefreshDatabase;

    public function test_event_for_tenant_a_does_not_create_deliveries_for_tenant_b(): void
    {
        Queue::fake();

        $ta = $this->createNotificationTenant();
        $tb = $this->createNotificationTenant();

        $destB = $this->createSharedInAppDestination($tb);
        $subB = NotificationSubscription::factory()->create([
            'tenant_id' => $tb->id,
            'event_key' => 'crm_request.created',
        ]);
        $subB->destinations()->attach($destB->id, [
            'delivery_mode' => 'immediate',
            'delay_seconds' => null,
            'order_index' => 0,
            'is_enabled' => true,
        ]);

        $crmA = CrmRequest::query()->create([
            'tenant_id' => $ta->id,
            'name' => 'A',
            'phone' => '+70000000001',
            'email' => null,
            'message' => 'x',
            'request_type' => 'tenant_booking',
            'source' => 'test',
            'channel' => 'web',
            'pipeline' => 'inbound',
            'status' => CrmRequest::STATUS_NEW,
            'last_activity_at' => now(),
        ]);

        $payload = app(CrmRequestNotificationPresenter::class)->payloadForCreated($ta, $crmA);
        app(NotificationEventRecorder::class)->record(
            $ta->id,
            'crm_request.created',
            class_basename(CrmRequest::class),
            (int) $crmA->id,
            $payload,
        );

        $this->assertSame(0, NotificationDelivery::query()->where('tenant_id', $tb->id)->count());
    }
}

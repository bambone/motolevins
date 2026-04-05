<?php

namespace Tests\Feature\Notifications\Drivers;

use App\Jobs\DispatchNotificationDeliveryJob;
use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\Models\User;
use App\NotificationCenter\NotificationChannelDriverFactory;
use App\NotificationCenter\NotificationChannelType;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\NotificationCenter\NotificationDestinationStatus;
use App\NotificationCenter\NotificationPayloadDto;
use App\Services\CurrentTenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishlink\WebPush\WebPush;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class WebPushNotificationDriverTest extends TestCase
{
    use NotificationTestHelpers;
    use RefreshDatabase;

    public function test_without_composer_package_marks_delivery_skipped(): void
    {
        if (class_exists(WebPush::class)) {
            $this->markTestSkipped('minishlink/web-push is installed; skipped-path test not applicable.');
        }

        $tenant = $this->createNotificationTenant();
        $user = User::factory()->create(['status' => 'active']);

        $dest = NotificationDestination::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'Push',
            'type' => NotificationChannelType::WebPush->value,
            'status' => NotificationDestinationStatus::Verified->value,
            'is_shared' => false,
            'config_json' => [],
        ]);

        $event = NotificationEvent::factory()->create([
            'tenant_id' => $tenant->id,
            'event_key' => 'crm_request.created',
            'subject_type' => 'CrmRequest',
            'subject_id' => 1,
            'severity' => 'normal',
            'dedupe_key' => null,
            'payload_json' => (new NotificationPayloadDto('T', 'B', null, null, []))->toArray(),
            'occurred_at' => now(),
        ]);

        $delivery = NotificationDelivery::factory()->create([
            'tenant_id' => $tenant->id,
            'event_id' => $event->id,
            'destination_id' => $dest->id,
            'channel_type' => NotificationChannelType::WebPush->value,
            'status' => NotificationDeliveryStatus::Queued->value,
            'queued_at' => now(),
        ]);

        app(CurrentTenantManager::class)->setTenant($tenant);
        $job = new DispatchNotificationDeliveryJob((int) $delivery->id);
        $job->handle(app(CurrentTenantManager::class), app(NotificationChannelDriverFactory::class));

        $delivery->refresh();
        $this->assertSame(NotificationDeliveryStatus::Skipped->value, $delivery->status);
    }
}

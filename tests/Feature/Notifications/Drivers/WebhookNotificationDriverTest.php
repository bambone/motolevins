<?php

namespace Tests\Feature\Notifications\Drivers;

use App\Jobs\DispatchNotificationDeliveryJob;
use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\NotificationChannelDriverFactory;
use App\NotificationCenter\NotificationChannelType;
use App\NotificationCenter\NotificationDeliveryStatus;
use App\NotificationCenter\NotificationDestinationStatus;
use App\NotificationCenter\NotificationPayloadDto;
use App\Services\CurrentTenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class WebhookNotificationDriverTest extends TestCase
{
    use NotificationTestHelpers;
    use RefreshDatabase;

    public function test_posts_json_with_hmac_when_secret_configured(): void
    {
        Http::fake([
            'https://example.com/*' => Http::response('ok', 200),
        ]);

        $tenant = $this->createNotificationTenant();

        $dest = NotificationDestination::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'name' => 'Hook',
            'type' => NotificationChannelType::Webhook->value,
            'status' => NotificationDestinationStatus::Verified->value,
            'is_shared' => true,
            'config_json' => [
                'url' => 'https://example.com/notify',
                'secret' => 'shared-secret',
            ],
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
            'channel_type' => NotificationChannelType::Webhook->value,
            'status' => NotificationDeliveryStatus::Queued->value,
            'queued_at' => now(),
        ]);

        app(CurrentTenantManager::class)->setTenant($tenant);
        $job = new DispatchNotificationDeliveryJob((int) $delivery->id);
        $job->handle(app(CurrentTenantManager::class), app(NotificationChannelDriverFactory::class));

        Http::assertSent(function (Request $request): bool {
            if ($request->header('X-Notification-Signature') === []) {
                return false;
            }
            $body = (string) $request->body();
            $sig = $request->header('X-Notification-Signature')[0];
            $expected = hash_hmac('sha256', $body, 'shared-secret');

            return hash_equals($expected, $sig);
        });

        $delivery->refresh();
        $this->assertContains($delivery->status, [NotificationDeliveryStatus::Sent->value, NotificationDeliveryStatus::Delivered->value]);
    }

    public function test_http_url_in_config_fails_before_request(): void
    {
        Http::fake();

        $tenant = $this->createNotificationTenant();

        $dest = NotificationDestination::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => null,
            'name' => 'Bad',
            'type' => NotificationChannelType::Webhook->value,
            'status' => NotificationDestinationStatus::Verified->value,
            'is_shared' => true,
            'config_json' => ['url' => 'http://example.com/insecure'],
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
            'channel_type' => NotificationChannelType::Webhook->value,
            'status' => NotificationDeliveryStatus::Queued->value,
            'queued_at' => now(),
        ]);

        app(CurrentTenantManager::class)->setTenant($tenant);
        $job = new DispatchNotificationDeliveryJob((int) $delivery->id);
        try {
            $job->handle(app(CurrentTenantManager::class), app(NotificationChannelDriverFactory::class));
        } catch (\InvalidArgumentException) {
            // Job rethrows for queue retry when validation fails before last attempt.
        }

        Http::assertNothingSent();
        $delivery->refresh();
        $this->assertSame(NotificationDeliveryStatus::Failed->value, $delivery->status);
    }
}

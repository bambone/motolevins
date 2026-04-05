<?php

namespace Tests\Feature\Notifications\Domain;

use App\Models\NotificationEvent;
use App\Models\NotificationSubscription;
use App\Product\CRM\Actions\CreateCrmRequestFromPublicForm;
use App\Product\CRM\DTO\PublicInboundContext;
use App\Product\CRM\DTO\PublicInboundSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Support\CreatesTenantsWithDomains;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class CrmRequestNotificationEventsTest extends TestCase
{
    use CreatesTenantsWithDomains;
    use NotificationTestHelpers;
    use RefreshDatabase;

    public function test_tenant_public_form_creates_notification_with_admin_deep_link_after_commit(): void
    {
        Mail::fake();
        Queue::fake();

        $tenant = $this->createTenantWithActiveDomain('crmnotif');
        $dest = $this->createSharedInAppDestination($tenant);
        $sub = NotificationSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'event_key' => 'crm_request.created',
        ]);
        $sub->destinations()->attach($dest->id, [
            'delivery_mode' => 'immediate',
            'delay_seconds' => null,
            'order_index' => 0,
            'is_enabled' => true,
        ]);

        $submission = new PublicInboundSubmission(
            requestType: 'tenant_booking',
            name: 'Renter',
            phone: '+79993332211',
            email: 'renter@example.test',
            message: 'Need bike',
            source: 'booking_form',
            channel: 'web',
        );

        $result = app(CreateCrmRequestFromPublicForm::class)->handle(
            PublicInboundContext::tenant($tenant->id),
            $submission,
        );

        $event = NotificationEvent::query()
            ->where('tenant_id', $tenant->id)
            ->where('event_key', 'crm_request.created')
            ->where('subject_id', $result->crmRequest->id)
            ->first();

        $this->assertNotNull($event);
        $actionUrl = $event->payload_json['action_url'] ?? null;
        $this->assertIsString($actionUrl);
        $this->assertStringContainsString('/admin', $actionUrl);
        $this->assertStringContainsString('/crm-requests/'.$result->crmRequest->id, $actionUrl);
    }
}

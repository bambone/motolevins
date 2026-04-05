<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationEvent;
use App\Models\Tenant;
use App\NotificationCenter\NotificationPayloadDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPayloadImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_payload_json_assignment_throws_after_persist(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'T',
            'slug' => 'im-'.substr(uniqid(), -10),
            'status' => 'active',
        ]);

        $event = NotificationEvent::factory()->create([
            'tenant_id' => $tenant->id,
            'event_key' => 'crm_request.created',
            'subject_type' => 'CrmRequest',
            'subject_id' => 1,
            'severity' => 'normal',
            'dedupe_key' => null,
            'payload_json' => (new NotificationPayloadDto('a', 'b', null, null, []))->toArray(),
            'occurred_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $event->payload_json = ['title' => 'x', 'body' => 'y'];
    }
}

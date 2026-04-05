<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationEvent;
use App\Models\User;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\NotificationPayloadDto;
use App\NotificationCenter\NotificationSeverity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class NotificationRecorderTest extends TestCase
{
    use NotificationTestHelpers;
    use RefreshDatabase;

    public function test_record_persists_occurred_at_actor_and_severity(): void
    {
        Queue::fake();

        $tenant = $this->createNotificationTenant();
        $user = User::factory()->create(['status' => 'active']);
        $payload = new NotificationPayloadDto('T', 'B', null, null, []);

        $out = app(NotificationEventRecorder::class)->record(
            $tenant->id,
            'crm_request.created',
            'CrmRequest',
            7,
            $payload,
            actorUserId: $user->id,
            severityOverride: NotificationSeverity::Low,
        );

        $this->assertFalse($out['duplicate']);
        $event = $out['event'];
        $this->assertInstanceOf(NotificationEvent::class, $event);
        $this->assertNotNull($event->occurred_at);
        $this->assertSame((string) $user->id, (string) $event->actor_user_id);
        $this->assertSame('low', $event->severity);
    }

    public function test_dedupe_collision_returns_duplicate_without_new_row(): void
    {
        Queue::fake();

        $tenant = $this->createNotificationTenant();
        $payload = new NotificationPayloadDto('t', 'b', null, null, []);
        $recorder = app(NotificationEventRecorder::class);

        $recorder->record(
            $tenant->id,
            'crm_request.created',
            'CrmRequest',
            1,
            $payload,
            dedupeKey: 'one',
        );
        $out = $recorder->record(
            $tenant->id,
            'crm_request.created',
            'CrmRequest',
            1,
            $payload,
            dedupeKey: 'one',
        );

        $this->assertTrue($out['duplicate']);
        $this->assertNull($out['event']);
        $this->assertSame(1, NotificationEvent::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_subject_type_mismatch_throws(): void
    {
        Queue::fake();

        $tenant = $this->createNotificationTenant();
        $payload = new NotificationPayloadDto('t', 'b', null, null, []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('subject_type mismatch');

        app(NotificationEventRecorder::class)->record(
            $tenant->id,
            'crm_request.created',
            'Lead',
            1,
            $payload,
        );
    }
}

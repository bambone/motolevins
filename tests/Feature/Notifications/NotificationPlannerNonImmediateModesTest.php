<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationSubscription;
use App\NotificationCenter\NotificationDeliveryMode;
use App\NotificationCenter\NotificationEventRecorder;
use App\NotificationCenter\NotificationPayloadDto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\NotificationTestHelpers;
use Tests\TestCase;

class NotificationPlannerNonImmediateModesTest extends TestCase
{
    use NotificationTestHelpers;
    use RefreshDatabase;

    /**
     * @return iterable<string, array{0: NotificationDeliveryMode}>
     */
    public static function nonImmediateModes(): iterable
    {
        yield 'escalation' => [NotificationDeliveryMode::Escalation];
        yield 'fallback' => [NotificationDeliveryMode::Fallback];
        yield 'digest' => [NotificationDeliveryMode::Digest];
    }

    #[DataProvider('nonImmediateModes')]
    public function test_non_immediate_pivot_mode_skips_immediate_delivery_plan(NotificationDeliveryMode $mode): void
    {
        Queue::fake();

        $tenant = $this->createNotificationTenant();
        $dest = $this->createSharedInAppDestination($tenant);
        $sub = NotificationSubscription::factory()->create([
            'tenant_id' => $tenant->id,
            'event_key' => 'crm_request.created',
        ]);
        $sub->destinations()->attach($dest->id, [
            'delivery_mode' => $mode->value,
            'delay_seconds' => null,
            'order_index' => 0,
            'is_enabled' => true,
        ]);

        $out = app(NotificationEventRecorder::class)->record(
            $tenant->id,
            'crm_request.created',
            'CrmRequest',
            1,
            new NotificationPayloadDto('t', 'b', null, null, []),
        );

        $this->assertSame([], $out['delivery_ids']);
    }
}

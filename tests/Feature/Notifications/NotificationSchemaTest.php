<?php

namespace Tests\Feature\Notifications;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_tables_exist(): void
    {
        foreach ([
            'notification_destinations',
            'notification_subscriptions',
            'notification_subscription_destinations',
            'notification_events',
            'notification_deliveries',
            'notification_delivery_attempts',
            'notification_push_subscriptions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), 'Missing table: '.$table);
        }
    }

    public function test_notification_events_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('notification_events', [
            'tenant_id', 'event_key', 'subject_type', 'subject_id', 'severity',
            'dedupe_key', 'payload_json', 'actor_user_id', 'occurred_at', 'created_at',
        ]));
    }
}

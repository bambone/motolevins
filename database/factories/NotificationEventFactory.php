<?php

namespace Database\Factories;

use App\Models\NotificationEvent;
use App\NotificationCenter\NotificationPayloadDto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationEvent>
 */
class NotificationEventFactory extends Factory
{
    protected $model = NotificationEvent::class;

    public function definition(): array
    {
        $payload = new NotificationPayloadDto('Title', 'Body', null, null, []);

        return [
            'tenant_id' => static function (): never {
                throw new \LogicException('NotificationEventFactory: pass tenant_id to create().');
            },
            'event_key' => 'crm_request.created',
            'subject_type' => 'CrmRequest',
            'subject_id' => 1,
            'severity' => 'normal',
            'dedupe_key' => null,
            'payload_json' => $payload->toArray(),
            'actor_user_id' => null,
            'occurred_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\NotificationDelivery;
use App\NotificationCenter\NotificationChannelType;
use App\NotificationCenter\NotificationDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    protected $model = NotificationDelivery::class;

    public function definition(): array
    {
        return [
            'tenant_id' => static function (): never {
                throw new \LogicException('NotificationDeliveryFactory: pass tenant_id to create().');
            },
            'event_id' => static function (): never {
                throw new \LogicException('NotificationDeliveryFactory: pass event_id to create().');
            },
            'subscription_id' => null,
            'destination_id' => static function (): never {
                throw new \LogicException('NotificationDeliveryFactory: pass destination_id to create().');
            },
            'channel_type' => NotificationChannelType::InApp->value,
            'status' => NotificationDeliveryStatus::Queued->value,
            'queued_at' => now(),
        ];
    }
}

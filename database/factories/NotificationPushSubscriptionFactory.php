<?php

namespace Database\Factories;

use App\Models\NotificationPushSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPushSubscription>
 */
class NotificationPushSubscriptionFactory extends Factory
{
    protected $model = NotificationPushSubscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => static function (): never {
                throw new \LogicException('NotificationPushSubscriptionFactory: pass tenant_id to create().');
            },
            'user_id' => static function (): never {
                throw new \LogicException('NotificationPushSubscriptionFactory: pass user_id to create().');
            },
            'destination_id' => static function (): never {
                throw new \LogicException('NotificationPushSubscriptionFactory: pass destination_id to create().');
            },
            'endpoint' => 'https://push.example.test/'.fake()->uuid(),
            'public_key' => 'BK'.fake()->sha256(),
            'auth_token' => fake()->sha256(),
            'user_agent' => 'PHPUnit',
            'device_label' => null,
            'last_seen_at' => now(),
            'is_active' => true,
        ];
    }
}

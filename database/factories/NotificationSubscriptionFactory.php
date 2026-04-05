<?php

namespace Database\Factories;

use App\Models\NotificationSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationSubscription>
 */
class NotificationSubscriptionFactory extends Factory
{
    protected $model = NotificationSubscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => static function (): never {
                throw new \LogicException('NotificationSubscriptionFactory: pass tenant_id to create().');
            },
            'user_id' => null,
            'name' => 'Test subscription',
            'event_key' => 'crm_request.created',
            'enabled' => true,
            'conditions_json' => null,
            'schedule_json' => null,
            'severity_min' => null,
            'created_by_user_id' => null,
        ];
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn () => ['user_id' => $userId]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['enabled' => false]);
    }
}

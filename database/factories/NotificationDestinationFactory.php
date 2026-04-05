<?php

namespace Database\Factories;

use App\Models\NotificationDestination;
use App\NotificationCenter\NotificationChannelType;
use App\NotificationCenter\NotificationDestinationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationDestination>
 */
class NotificationDestinationFactory extends Factory
{
    protected $model = NotificationDestination::class;

    public function definition(): array
    {
        return [
            'tenant_id' => static function (): never {
                throw new \LogicException('NotificationDestinationFactory: pass tenant_id to create().');
            },
            'user_id' => null,
            'name' => 'Test destination',
            'type' => NotificationChannelType::InApp->value,
            'status' => NotificationDestinationStatus::Verified->value,
            'is_shared' => true,
            'config_json' => [],
        ];
    }

    public function personalForUser(int $userId): static
    {
        return $this->state(fn () => [
            'user_id' => $userId,
            'is_shared' => false,
        ]);
    }
}

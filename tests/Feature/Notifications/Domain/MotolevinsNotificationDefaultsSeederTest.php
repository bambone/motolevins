<?php

namespace Tests\Feature\Notifications\Domain;

use App\Models\NotificationDestination;
use App\Models\NotificationSubscription;
use App\Models\Tenant;
use Database\Seeders\MotolevinsNotificationDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotolevinsNotificationDefaultsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_does_not_require_user_ids(): void
    {
        Tenant::query()->firstOrCreate(
            ['slug' => 'motolevins'],
            ['name' => 'Motolevins', 'status' => 'active'],
        );

        $this->seed(MotolevinsNotificationDefaultsSeeder::class);
        $this->seed(MotolevinsNotificationDefaultsSeeder::class);

        $tenant = Tenant::query()->where('slug', 'motolevins')->first();
        $this->assertNotNull($tenant);

        $this->assertSame(1, NotificationDestination::query()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(1, NotificationSubscription::query()->where('tenant_id', $tenant->id)->where('event_key', 'crm_request.created')->count());

        $sub = NotificationSubscription::query()->where('tenant_id', $tenant->id)->where('event_key', 'crm_request.created')->first();
        $this->assertNotNull($sub);
        $this->assertNull($sub->user_id);
        $this->assertGreaterThanOrEqual(1, $sub->destinations()->count());
    }
}

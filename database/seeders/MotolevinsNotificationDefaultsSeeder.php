<?php

namespace Database\Seeders;

use App\Models\NotificationDestination;
use App\Models\NotificationSubscription;
use App\Models\Tenant;
use App\NotificationCenter\NotificationChannelType;
use App\NotificationCenter\NotificationDestinationStatus;
use Illuminate\Database\Seeder;

/**
 * Optional tenant-specific bootstrap (tenant slug `motolevins`).
 * Not wired into {@see DatabaseSeeder} by default: run explicitly after notification core is stable,
 * or from deployment playbooks when you intend a production behavior change for that tenant.
 *
 * Idempotent: no hard-coded user ids; safe to re-run.
 */
class MotolevinsNotificationDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->where('slug', 'motolevins')->first();
        if ($tenant === null) {
            return;
        }

        $inApp = NotificationDestination::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'В кабинете (общий)',
            ],
            [
                'user_id' => null,
                'type' => NotificationChannelType::InApp->value,
                'status' => NotificationDestinationStatus::Verified->value,
                'is_shared' => true,
                'config_json' => [],
            ],
        );

        $subscription = NotificationSubscription::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Новая заявка → in-app',
                'event_key' => 'crm_request.created',
            ],
            [
                'user_id' => null,
                'enabled' => true,
                'conditions_json' => null,
                'schedule_json' => null,
                'severity_min' => null,
                'created_by_user_id' => null,
            ],
        );

        if (! $subscription->destinations()->where('notification_destinations.id', $inApp->id)->exists()) {
            $subscription->destinations()->attach($inApp->id, [
                'delivery_mode' => 'immediate',
                'delay_seconds' => null,
                'order_index' => 0,
                'is_enabled' => true,
            ]);
        }
    }
}

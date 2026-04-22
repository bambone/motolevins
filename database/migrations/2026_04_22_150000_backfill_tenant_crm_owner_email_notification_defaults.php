<?php

use App\Models\NotificationSubscription;
use App\Models\Tenant;
use App\NotificationCenter\TenantCrmNewRequestEmailDefaults;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = app(TenantCrmNewRequestEmailDefaults::class);

        Tenant::query()
            ->orderBy('id')
            ->chunkById(100, function ($tenants) use ($defaults): void {
                foreach ($tenants as $tenant) {
                    $defaults->ensureForTenant($tenant, true);
                }
            });

        NotificationSubscription::query()
            ->where('event_key', 'crm_request.created')
            ->where('name', TenantCrmNewRequestEmailDefaults::SUBSCRIPTION_NAME)
            ->update(['enabled' => true]);
    }
};

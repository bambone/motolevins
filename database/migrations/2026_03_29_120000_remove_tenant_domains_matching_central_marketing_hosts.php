<?php

use App\Models\TenantDomain;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Marketing apex (TENANCY_CENTRAL_DOMAINS) must never be a row in tenant_domains — it breaks the split-host model.
     */
    public function up(): void
    {
        $centrals = array_values(array_filter(array_map(
            static fn (string $h): string => TenantDomain::normalizeHost($h),
            config('tenancy.central_domains', [])
        )));

        if ($centrals === []) {
            return;
        }

        TenantDomain::query()->whereIn('host', $centrals)->delete();
    }

    public function down(): void
    {
        // Irreversible cleanup; restoring mistaken rows would require knowing tenant_id.
    }
};

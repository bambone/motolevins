<?php

namespace App\Console\Commands\Concerns;

use App\Models\Tenant;

trait ResolvesTenantArgument
{
    protected function resolveTenant(string $key): Tenant
    {
        if (ctype_digit($key)) {
            return Tenant::query()
                ->where('status', 'active')
                ->whereKey((int) $key)
                ->firstOrFail();
        }

        return Tenant::query()
            ->where('status', 'active')
            ->where('slug', $key)
            ->firstOrFail();
    }
}

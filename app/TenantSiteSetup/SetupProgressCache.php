<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

use Illuminate\Support\Facades\Cache;

final class SetupProgressCache
{
    public static function forget(int $tenantId): void
    {
        Cache::forget(self::key($tenantId));
    }

    public static function key(int $tenantId): string
    {
        return 'tenant_setup_summary.'.$tenantId;
    }
}

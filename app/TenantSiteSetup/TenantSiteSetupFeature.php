<?php

declare(strict_types=1);

namespace App\TenantSiteSetup;

final class TenantSiteSetupFeature
{
    public static function enabled(): bool
    {
        return (bool) config('features.tenant_site_setup_framework', true);
    }
}

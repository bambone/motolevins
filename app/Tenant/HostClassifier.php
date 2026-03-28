<?php

namespace App\Tenant;

use App\Models\TenantDomain;

class HostClassifier
{
    /**
     * Marketing (central) + platform panel hosts: never resolve to a tenant site.
     */
    public function isNonTenantHost(string $host): bool
    {
        $host = TenantDomain::normalizeHost($host);

        foreach (config('tenancy.central_domains', []) as $central) {
            if ($central !== '' && $host === TenantDomain::normalizeHost((string) $central)) {
                return true;
            }
        }

        return $this->isPlatformPanelHost($host);
    }

    /**
     * Filament Platform console: only platform_host and platform.* subdomains.
     */
    public function isPlatformPanelHost(string $host): bool
    {
        $host = TenantDomain::normalizeHost($host);
        $platform = TenantDomain::normalizeHost((string) config('app.platform_host', ''));

        if ($platform !== '' && $host === $platform) {
            return true;
        }

        return str_starts_with($host, 'platform.');
    }
}

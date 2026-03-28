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

        return $this->isCentralMarketingHost($host) || $this->isPlatformHost($host);
    }

    public function isCentralMarketingHost(string $host): bool
    {
        $host = TenantDomain::normalizeHost($host);

        foreach (config('tenancy.central_domains', []) as $central) {
            if ($central !== '' && $host === TenantDomain::normalizeHost((string) $central)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filament Platform console: configured platform_host and platform.* subdomains.
     */
    public function isPlatformHost(string $host): bool
    {
        $host = TenantDomain::normalizeHost($host);
        $platform = TenantDomain::normalizeHost((string) config('app.platform_host', ''));

        if ($platform !== '' && $host === $platform) {
            return true;
        }

        return str_starts_with($host, 'platform.');
    }

    /**
     * @deprecated Use {@see isPlatformHost()}
     */
    public function isPlatformPanelHost(string $host): bool
    {
        return $this->isPlatformHost($host);
    }
}

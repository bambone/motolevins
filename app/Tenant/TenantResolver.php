<?php

namespace App\Tenant;

use App\Models\TenantDomain;
use Illuminate\Support\Facades\Cache;

class TenantResolver
{
    public function __construct(
        protected HostClassifier $hostClassifier
    ) {}

    public static function tenantHostCacheKey(string $normalizedHost): string
    {
        return 'tenant_host:'.$normalizedHost;
    }

    public function resolve(string $host): CurrentTenant
    {
        $normalized = TenantDomain::normalizeHost($host);

        if ($this->hostClassifier->isNonTenantHost($normalized)) {
            return new CurrentTenant(null, null, true, $normalized);
        }

        $payload = $this->rememberActiveDomainPayload($normalized);

        if ($payload['tenant_domain_id'] === null || $payload['tenant_id'] === null) {
            return new CurrentTenant(null, null, false, $normalized);
        }

        $domain = TenantDomain::query()
            ->with('tenant')
            ->whereKey($payload['tenant_domain_id'])
            ->where('tenant_id', $payload['tenant_id'])
            ->where('status', TenantDomain::STATUS_ACTIVE)
            ->first();

        if ($domain === null || $domain->tenant === null) {
            Cache::forget(self::tenantHostCacheKey($normalized));

            return new CurrentTenant(null, null, false, $normalized);
        }

        return new CurrentTenant($domain->tenant, $domain, false, $normalized);
    }

    /**
     * @return array{tenant_domain_id: int|null, tenant_id: int|null}
     */
    public function rememberActiveDomainPayload(string $normalizedHost): array
    {
        $key = self::tenantHostCacheKey($normalizedHost);
        $ttl = (int) config('tenancy.cache_ttl', 300);

        return Cache::remember($key, $ttl, function () use ($normalizedHost) {
            $domain = TenantDomain::query()
                ->where('host', $normalizedHost)
                ->where('status', TenantDomain::STATUS_ACTIVE)
                ->first();

            if ($domain === null) {
                return ['tenant_domain_id' => null, 'tenant_id' => null];
            }

            return [
                'tenant_domain_id' => $domain->id,
                'tenant_id' => $domain->tenant_id,
            ];
        });
    }

    public function forgetCacheForHost(string $host): void
    {
        Cache::forget(self::tenantHostCacheKey(TenantDomain::normalizeHost($host)));
    }
}

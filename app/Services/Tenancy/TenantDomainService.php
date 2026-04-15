<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\TenantDomain;
use Illuminate\Support\Str;

class TenantDomainService
{
    public function __construct(
        protected TenantDomainHostRules $hostRules
    ) {}

    public function createDefaultSubdomain(Tenant $tenant, string $slug): TenantDomain
    {
        $root = (string) config('tenancy.root_domain', '');
        $fallbackHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $suffix = $root !== '' ? $root : ($fallbackHost ?: 'localhost');
        $hostCandidate = TenantDomain::normalizeHost($slug.'.'.$suffix);
        $host = $this->hostRules->assertValidHostFormat($hostCandidate, TenantDomain::TYPE_SUBDOMAIN);

        $tenantAlreadyHasPrimary = TenantDomain::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_primary', true)
            ->exists();

        return TenantDomain::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'host' => $host],
            [
                'type' => TenantDomain::TYPE_SUBDOMAIN,
                'is_primary' => ! $tenantAlreadyHasPrimary,
                'status' => TenantDomain::STATUS_ACTIVE,
                'verification_method' => null,
                'verification_token' => null,
                'ssl_status' => TenantDomain::SSL_NOT_REQUIRED,
                'verified_at' => now(),
                'activated_at' => now(),
            ]
        );
    }

    public function addCustomDomain(Tenant $tenant, string $domain): TenantDomain
    {
        $canonical = $this->hostRules->validateAndCanonicalize(
            $domain,
            $tenant->id,
            null,
            TenantDomain::TYPE_CUSTOM
        );

        return TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'host' => $canonical,
            'type' => TenantDomain::TYPE_CUSTOM,
            'is_primary' => false,
            'status' => TenantDomain::STATUS_PENDING,
            'verification_method' => 'dns_txt',
            'verification_token' => $this->generateVerificationToken(),
            'ssl_status' => TenantDomain::SSL_PENDING,
        ]);
    }

    public function setPrimaryDomain(TenantDomain $target): void
    {
        TenantDomain::query()
            ->where('tenant_id', $target->tenant_id)
            ->update(['is_primary' => false]);

        $target->update(['is_primary' => true]);
    }

    public function generateVerificationToken(): string
    {
        return 'rb-'.Str::random(40);
    }
}

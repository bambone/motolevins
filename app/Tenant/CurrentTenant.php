<?php

namespace App\Tenant;

use App\Models\Tenant;
use App\Models\TenantDomain;

class CurrentTenant
{
    public function __construct(
        public readonly ?Tenant $tenant,
        public readonly ?TenantDomain $domain = null,
        public readonly bool $isNonTenantHost = false,
        public readonly ?string $resolvedHost = null,
    ) {}

    public function id(): ?int
    {
        return $this->tenant?->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}

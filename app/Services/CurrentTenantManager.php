<?php

namespace App\Services;

use App\Models\Tenant;
use App\Tenant\CurrentTenant;

/**
 * @deprecated Prefer App\Tenant\CurrentTenant bound per request. Kept for queue/tests calling setTenant().
 */
class CurrentTenantManager
{
    protected ?Tenant $tenant = null;

    protected bool $resolved = false;

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->resolved = true;
        app()->instance(CurrentTenant::class, new CurrentTenant($tenant, null, false, null));
    }

    public function getTenant(): ?Tenant
    {
        if (app()->bound(CurrentTenant::class)) {
            return app(CurrentTenant::class)->tenant;
        }

        return $this->tenant;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function clear(): void
    {
        $this->tenant = null;
        $this->resolved = false;
        app()->instance(CurrentTenant::class, new CurrentTenant(null, null, true, null));
    }

    public function getId(): ?int
    {
        return $this->getTenant()?->id;
    }
}

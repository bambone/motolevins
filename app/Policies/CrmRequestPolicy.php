<?php

namespace App\Policies;

use App\Auth\AccessRoles;
use App\Models\CrmRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class CrmRequestPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(AccessRoles::platformRoles())) {
            return true;
        }

        if (! $user->can('manage_leads')) {
            return false;
        }

        $tenant = currentTenant();
        if ($tenant === null) {
            return false;
        }

        return $user->tenants()->where('tenant_id', $tenant->id)->exists();
    }

    public function view(User $user, CrmRequest $crmRequest): bool
    {
        if ($user->hasAnyRole(AccessRoles::platformRoles())) {
            return $crmRequest->tenant_id === null;
        }

        if (! $user->can('manage_leads')) {
            return false;
        }

        return $crmRequest->tenant_id !== null
            && $this->userCanAccessTenant($user, $crmRequest)
            && $this->belongsToCurrentTenant($crmRequest);
    }

    public function create(User $user): bool
    {
        if ($user->hasAnyRole(AccessRoles::platformRoles())) {
            return true;
        }

        if (! $user->can('manage_leads')) {
            return false;
        }

        $tenant = currentTenant();
        if ($tenant === null) {
            return false;
        }

        return $user->tenants()->where('tenant_id', $tenant->id)->exists();
    }

    public function update(User $user, CrmRequest $crmRequest): bool
    {
        return $this->view($user, $crmRequest);
    }

    public function delete(User $user, CrmRequest $crmRequest): bool
    {
        return $this->view($user, $crmRequest);
    }

    public function restore(User $user, CrmRequest $crmRequest): bool
    {
        return $this->view($user, $crmRequest);
    }

    public function forceDelete(User $user, CrmRequest $crmRequest): bool
    {
        return $this->view($user, $crmRequest);
    }
}

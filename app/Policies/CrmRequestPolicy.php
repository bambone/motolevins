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
        return $user->hasAnyRole(AccessRoles::platformRoles())
            || $user->can('manage_leads');
    }

    public function view(User $user, CrmRequest $crmRequest): bool
    {
        if ($user->hasAnyRole(AccessRoles::platformRoles())) {
            return $crmRequest->tenant_id === null;
        }

        if ($user->can('manage_leads')) {
            return $crmRequest->tenant_id !== null
                && $this->userCanAccessTenant($user, $crmRequest);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(AccessRoles::platformRoles())
            || $user->can('manage_leads');
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

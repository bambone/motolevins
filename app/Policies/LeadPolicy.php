<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class LeadPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('manage_leads');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads') && $this->userCanAccessTenant($user, $lead);
    }

    public function create(User $user): bool
    {
        if (! $user->can('manage_leads')) {
            return false;
        }

        $tenant = currentTenant();
        if ($tenant === null) {
            return false;
        }

        return $user->tenants()->where('tenant_id', $tenant->id)->exists();
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads') && $this->userCanAccessTenant($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads') && $this->userCanAccessTenant($user, $lead);
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads') && $this->userCanAccessTenant($user, $lead);
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads') && $this->userCanAccessTenant($user, $lead);
    }
}

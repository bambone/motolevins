<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantLocation;
use App\Models\User;

class TenantLocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_motorcycles');
    }

    public function view(User $user, TenantLocation $tenantLocation): bool
    {
        return $user->can('manage_motorcycles');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_motorcycles');
    }

    public function update(User $user, TenantLocation $tenantLocation): bool
    {
        return $user->can('manage_motorcycles');
    }

    public function delete(User $user, TenantLocation $tenantLocation): bool
    {
        return $user->can('manage_motorcycles');
    }

    public function restore(User $user, TenantLocation $tenantLocation): bool
    {
        return $user->can('manage_motorcycles');
    }

    public function forceDelete(User $user, TenantLocation $tenantLocation): bool
    {
        return $user->can('manage_motorcycles');
    }
}

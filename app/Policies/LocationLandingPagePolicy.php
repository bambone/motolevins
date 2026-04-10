<?php

namespace App\Policies;

use App\Models\LocationLandingPage;
use App\Models\User;

class LocationLandingPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_pages');
    }

    public function view(User $user, LocationLandingPage $locationLandingPage): bool
    {
        return $user->can('manage_pages');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_pages');
    }

    public function update(User $user, LocationLandingPage $locationLandingPage): bool
    {
        return $user->can('manage_pages');
    }

    public function delete(User $user, LocationLandingPage $locationLandingPage): bool
    {
        return $user->can('manage_pages');
    }
}

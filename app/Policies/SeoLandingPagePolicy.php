<?php

namespace App\Policies;

use App\Models\SeoLandingPage;
use App\Models\User;

class SeoLandingPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_pages');
    }

    public function view(User $user, SeoLandingPage $seoLandingPage): bool
    {
        return $user->can('manage_pages');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_pages');
    }

    public function update(User $user, SeoLandingPage $seoLandingPage): bool
    {
        return $user->can('manage_pages');
    }

    public function delete(User $user, SeoLandingPage $seoLandingPage): bool
    {
        return $user->can('manage_pages');
    }
}

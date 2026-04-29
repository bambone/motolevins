<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReviewImportSource;
use App\Models\User;

class ReviewImportSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_reviews');
    }

    public function view(User $user, ReviewImportSource $reviewImportSource): bool
    {
        return $user->can('manage_reviews');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_reviews');
    }

    public function update(User $user, ReviewImportSource $reviewImportSource): bool
    {
        return $user->can('manage_reviews');
    }

    public function delete(User $user, ReviewImportSource $reviewImportSource): bool
    {
        return $user->can('manage_reviews');
    }
}

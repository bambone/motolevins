<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReviewImportCandidate;
use App\Models\User;

class ReviewImportCandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_reviews');
    }

    public function view(User $user, ReviewImportCandidate $reviewImportCandidate): bool
    {
        return $user->can('manage_reviews');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_reviews');
    }

    public function update(User $user, ReviewImportCandidate $reviewImportCandidate): bool
    {
        return $user->can('manage_reviews');
    }

    public function delete(User $user, ReviewImportCandidate $reviewImportCandidate): bool
    {
        return $user->can('manage_reviews');
    }
}

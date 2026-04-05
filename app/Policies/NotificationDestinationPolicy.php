<?php

namespace App\Policies;

use App\Models\NotificationDestination;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class NotificationDestinationPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('manage_notifications') || $user->can('manage_notification_destinations');
    }

    public function view(User $user, NotificationDestination $destination): bool
    {
        if (! $this->belongsToCurrentTenant($destination)) {
            return false;
        }

        if ($user->can('manage_notifications')) {
            return true;
        }

        return $destination->user_id !== null && (int) $destination->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_notifications') || $user->can('manage_notification_destinations');
    }

    public function update(User $user, NotificationDestination $destination): bool
    {
        return $this->view($user, $destination);
    }

    public function delete(User $user, NotificationDestination $destination): bool
    {
        return $user->can('manage_notifications');
    }
}

<?php

namespace App\Policies;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class NotificationDeliveryPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('view_notification_history') || $user->can('manage_notifications');
    }

    public function view(User $user, NotificationDelivery $delivery): bool
    {
        if (! $this->belongsToCurrentTenant($delivery)) {
            return false;
        }

        return $user->can('view_notification_history') || $user->can('manage_notifications');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, NotificationDelivery $delivery): bool
    {
        return false;
    }

    public function delete(User $user, NotificationDelivery $delivery): bool
    {
        return false;
    }
}

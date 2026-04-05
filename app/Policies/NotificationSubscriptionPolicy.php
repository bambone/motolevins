<?php

namespace App\Policies;

use App\Models\NotificationSubscription;
use App\Models\User;
use App\Policies\Concerns\ChecksTenantOwnership;

class NotificationSubscriptionPolicy
{
    use ChecksTenantOwnership;

    public function viewAny(User $user): bool
    {
        return $user->can('manage_notifications') || $user->can('manage_notification_subscriptions');
    }

    public function view(User $user, NotificationSubscription $subscription): bool
    {
        if (! $this->belongsToCurrentTenant($subscription)) {
            return false;
        }

        if ($user->can('manage_notifications')) {
            return true;
        }

        return $subscription->user_id !== null && (int) $subscription->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('manage_notifications') || $user->can('manage_notification_subscriptions');
    }

    public function update(User $user, NotificationSubscription $subscription): bool
    {
        return $this->view($user, $subscription);
    }

    public function delete(User $user, NotificationSubscription $subscription): bool
    {
        return $user->can('manage_notifications');
    }
}

<?php

namespace App\NotificationCenter;

use App\Jobs\DispatchNotificationDeliveryJob;
use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\Models\NotificationSubscription;
use Illuminate\Support\Carbon;

final class NotificationDeliveryPlanner
{
    public function __construct(
        private readonly NotificationSchedulePolicy $schedulePolicy,
    ) {}

    /**
     * Create queued deliveries for matching subscription rows (immediate mode only in core v1).
     *
     * @return list<NotificationDelivery>
     */
    public function planFromSubscription(
        NotificationEvent $event,
        NotificationSubscription $subscription,
    ): array {
        if ((int) $subscription->tenant_id !== (int) $event->tenant_id) {
            return [];
        }

        $created = [];
        $scheduleAllows = $this->schedulePolicy->allowsImmediateDelivery($subscription, $event);

        foreach ($subscription->destinations as $destination) {
            if ((int) $destination->tenant_id !== (int) $event->tenant_id) {
                continue;
            }

            if (! $destination->pivot->is_enabled) {
                continue;
            }

            $mode = NotificationDeliveryMode::tryFrom((string) $destination->pivot->delivery_mode)
                ?? NotificationDeliveryMode::Immediate;

            if ($mode !== NotificationDeliveryMode::Immediate) {
                continue;
            }

            if (! $scheduleAllows) {
                continue;
            }

            if (! $this->destinationEligible($destination)) {
                continue;
            }

            $channelType = NotificationChannelType::tryFrom($destination->type);
            if ($channelType === null) {
                continue;
            }

            $delivery = NotificationDelivery::query()->create([
                'tenant_id' => $event->tenant_id,
                'event_id' => $event->id,
                'subscription_id' => $subscription->id,
                'destination_id' => $destination->id,
                'channel_type' => $channelType->value,
                'status' => NotificationDeliveryStatus::Queued->value,
                'queued_at' => Carbon::now(),
            ]);

            $created[] = $delivery;

            DispatchNotificationDeliveryJob::dispatch($delivery->id)->afterCommit();
        }

        return $created;
    }

    private function destinationEligible(NotificationDestination $destination): bool
    {
        if ($destination->disabled_at !== null) {
            return false;
        }

        if ($destination->type === NotificationChannelType::InApp->value) {
            return in_array($destination->status, [
                NotificationDestinationStatus::Verified->value,
                NotificationDestinationStatus::Draft->value,
            ], true);
        }

        return $destination->status === NotificationDestinationStatus::Verified->value;
    }
}

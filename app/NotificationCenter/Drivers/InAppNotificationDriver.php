<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\NotificationDeliveryStatus;
use Illuminate\Support\Carbon;

final class InAppNotificationDriver implements NotificationChannelDriver
{
    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): void {
        $delivery->update([
            'status' => NotificationDeliveryStatus::Delivered->value,
            'delivered_at' => Carbon::now(),
            'sent_at' => Carbon::now(),
        ]);
    }
}

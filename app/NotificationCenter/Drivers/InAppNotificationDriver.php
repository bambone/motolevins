<?php

namespace App\NotificationCenter\Drivers;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\ChannelSendResult;
use App\NotificationCenter\Contracts\NotificationChannelDriver;
use App\NotificationCenter\NotificationDeliveryStatus;
use Illuminate\Support\Carbon;

final class InAppNotificationDriver implements NotificationChannelDriver
{
    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): ChannelSendResult {
        $now = Carbon::now();

        return new ChannelSendResult(
            status: NotificationDeliveryStatus::Delivered,
            sentAt: $now,
            deliveredAt: $now,
            responseJson: ['channel' => 'in_app'],
        );
    }
}

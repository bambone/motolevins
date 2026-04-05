<?php

namespace App\NotificationCenter\Contracts;

use App\Models\NotificationDelivery;
use App\Models\NotificationDestination;
use App\Models\NotificationEvent;
use App\NotificationCenter\ChannelSendResult;

interface NotificationChannelDriver
{
    /**
     * Perform the external (or in-app) send only; do not mutate {@see NotificationDelivery} here.
     *
     * @throws \Throwable on hard failures (job maps to failed attempt / retry)
     */
    public function send(
        NotificationDelivery $delivery,
        NotificationEvent $event,
        NotificationDestination $destination,
    ): ChannelSendResult;
}
